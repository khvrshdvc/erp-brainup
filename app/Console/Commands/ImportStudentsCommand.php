<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Branch;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\CredentialGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportStudentsCommand extends Command
{
    protected $signature = 'students:import
        {file : CSV fayl: full_name, phone, email, password, groups}
        {--branch= : Filial ID raqami yoki nomi}
        {--dry-run : Bazaga yozmasdan faqat tekshirish}';

    protected $description = 'O‘quvchilarni CSV fayldan yaratadi va guruhlarga biriktiradi';

    private array $warnings = [];

    public function handle(CredentialGenerator $credentials): int
    {
        $branch = $this->branch();

        if ($branch === null) {
            $this->error('Filial topilmadi. --branch=ID yoki --branch="Chirchiq filiali" ko‘rsating.');

            return self::FAILURE;
        }

        $rows = $this->rows((string) $this->argument('file'));

        if ($rows === null) {
            return self::FAILURE;
        }

        $groups = StudyGroup::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->get()
            ->keyBy(fn (StudyGroup $group) => mb_strtolower($group->name));

        $missing = $rows->flatMap(fn (array $row) => $row['groups'])
            ->unique()
            ->reject(fn (string $name) => $groups->has(mb_strtolower($name)))
            ->values();

        if ($missing->isNotEmpty()) {
            $this->error("“{$branch->name}” filialida bu guruhlar yo‘q: ".$missing->implode(', '));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $stats = ['created' => 0, 'existing' => 0, 'linked' => 0, 'skipped' => 0];
        $issued = [];
        $touched = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $student = $this->student($row, $branch, $credentials, $stats, $issued);

                if ($student === null) {
                    continue;
                }

                foreach ($row['groups'] as $name) {
                    $group = $groups[mb_strtolower($name)];
                    $stats['linked'] += count($group->students()->syncWithoutDetaching([$student->id])['attached']);
                    $touched[$group->id] = $group;
                }
            }

            foreach ($touched as $group) {
                $count = $group->students()->count();

                if ($count > $group->capacity) {
                    $this->warnings[] = "{$group->name}: sig‘im {$group->capacity} dan {$count} ga oshirildi.";
                    $group->update(['capacity' => $count]);
                }
            }
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }

        $this->table(['Natija', 'Soni'], [
            ['Yangi o‘quvchi', $stats['created']],
            ['Avvaldan bor edi', $stats['existing']],
            ['Guruhga biriktirildi', $stats['linked']],
            ['O‘tkazib yuborildi', $stats['skipped']],
        ]);

        if ($dryRun) {
            $this->info('Sinov rejimi: bazaga hech narsa yozilmadi.');

            return self::SUCCESS;
        }

        if ($issued !== []) {
            $path = $this->saveIssued($issued);
            $this->info("Yaratilgan login va parollar: {$path}");
            $this->warn('O‘quvchilarga tarqatgandan keyin bu faylni o‘chiring.');
        }

        if ($stats['existing'] > 0) {
            $this->warn('Avvaldan bor o‘quvchilarning paroli o‘zgartirilmadi.');
        }

        return self::SUCCESS;
    }

    private function student(array $row, Branch $branch, CredentialGenerator $credentials, array &$stats, array &$issued): ?User
    {
        $line = $row['line'];
        $email = $row['email'] !== '' ? mb_strtolower($row['email']) : null;

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->warnings[] = "Qator {$line}: “{$email}” email emas, o‘tkazib yuborildi.";
            $stats['skipped']++;

            return null;
        }

        $existing = $email !== null ? User::where('email', $email)->first() : null;

        if ($existing !== null) {
            $sameStudent = $existing->role === Role::Student
                && (int) $existing->branch_id === $branch->id
                && mb_strtolower($existing->name) === mb_strtolower($row['full_name']);

            if (! $sameStudent) {
                $this->warnings[] = "Qator {$line}: {$email} boshqa foydalanuvchiga tegishli, o‘tkazib yuborildi.";
                $stats['skipped']++;

                return null;
            }

            $stats['existing']++;

            return $existing;
        }

        $local = $credentials->localPhone($row['phone']);
        $phone = $local !== null ? '+998'.$local : null;

        if ($row['phone'] !== '' && $phone === null) {
            $this->warnings[] = "Qator {$line}: {$row['full_name']} — telefon noto‘g‘ri, telefonsiz yaratildi.";
        }

        if ($phone !== null && User::where('phone', $phone)->exists()) {
            $this->warnings[] = "Qator {$line}: {$row['full_name']} — {$phone} band, telefonsiz yaratildi.";
            $phone = null;
        }

        $email ??= $this->login($row['full_name'], $credentials);

        if ($email === null) {
            $this->warnings[] = "Qator {$line}: {$row['full_name']} uchun login yaratib bo‘lmadi, o‘tkazib yuborildi.";
            $stats['skipped']++;

            return null;
        }

        $password = $row['password'] !== '' ? $row['password'] : $this->password();

        $student = User::create([
            'name' => $row['full_name'],
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => Role::Student,
            'status' => UserStatus::Active,
            'branch_id' => $branch->id,
        ]);

        $stats['created']++;

        if ($row['email'] === '' || $row['password'] === '') {
            $issued[] = [$student->name, $email, $password];
        }

        return $student;
    }

    private function login(string $name, CredentialGenerator $credentials): ?string
    {
        $words = array_values(array_filter(explode('.', $credentials->slug($name))));

        if ($words === []) {
            return null;
        }

        $local = count($words) > 1 ? $words[1].'.'.$words[0] : $words[0];
        $domain = config('erp.login_domain');
        $email = "{$local}@{$domain}";

        for ($n = 2; User::where('email', $email)->exists(); $n++) {
            $email = "{$local}{$n}@{$domain}";
        }

        return $email;
    }

    private function password(): string
    {
        do {
            $password = (string) random_int(100000, 999999);
        } while (count(array_unique(str_split($password))) < 3
            || str_contains('0123456789', $password)
            || str_contains('9876543210', $password));

        return $password;
    }

    private function branch(): ?Branch
    {
        $option = trim((string) $this->option('branch'));

        if ($option === '') {
            return Branch::count() === 1 ? Branch::first() : null;
        }

        return ctype_digit($option) ? Branch::find((int) $option) : Branch::where('name', $option)->first();
    }

    private function rows(string $path): ?Collection
    {
        if (! is_file($path) || ($handle = fopen($path, 'r')) === false) {
            $this->error("Fayl topilmadi: {$path}");

            return null;
        }

        $header = null;
        $rows = collect();
        $emails = [];
        $line = 0;

        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(fn ($column) => mb_strtolower(trim(ltrim((string) $column, "\xEF\xBB\xBF"))), $data);

                if (! in_array('full_name', $header, true)) {
                    fclose($handle);
                    $this->error('CSV birinchi qatorida full_name ustuni bo‘lishi kerak.');

                    return null;
                }

                continue;
            }

            if ($data === [null] || implode('', $data) === '') {
                continue;
            }

            $row = array_combine($header, array_pad(array_slice(array_map('trim', $data), 0, count($header)), count($header), ''));
            $name = preg_replace('/\s+/u', ' ', $row['full_name'] ?? '');
            $email = mb_strtolower($row['email'] ?? '');

            if ($name === '') {
                $this->warnings[] = "Qator {$line}: ism yo‘q, o‘tkazib yuborildi.";

                continue;
            }

            if ($email !== '' && isset($emails[$email])) {
                $this->warnings[] = "Qator {$line}: {$email} {$emails[$email]}-qatorda ham bor.";
            }

            $emails[$email] ??= $line;

            $rows->push([
                'line' => $line,
                'full_name' => $name,
                'phone' => $row['phone'] ?? '',
                'email' => $email,
                'password' => $row['password'] ?? '',
                'groups' => array_values(array_filter(array_map('trim', preg_split('/[|;]/', $row['groups'] ?? '')))),
            ]);
        }

        fclose($handle);

        if ($rows->isEmpty()) {
            $this->error('Faylda o‘quvchi yo‘q.');

            return null;
        }

        return $rows;
    }

    private function saveIssued(array $issued): string
    {
        $path = storage_path('app/private/credentials-'.now()->format('Ymd-His').'.csv');
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['full_name', 'email', 'password'], ',', '"', '');

        foreach ($issued as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        fclose($handle);

        return $path;
    }
}
