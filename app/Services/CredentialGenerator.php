<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Logins (emails) and starting passwords for accounts created by admins.
 *
 * Logins are built from the name, phone or branch on config('erp.login_domain'): a branch admin
 * gets the branch name, a teacher ism.familiya, and a student the 9-digit phone number or
 * ism.familiya plus two digits. Passwords use random_int() only.
 */
class CredentialGenerator
{
    /** Words children know (4 to 6 Latin letters, no apostrophes) for student passwords. */
    public const WORDS = [
        'olma', 'anor', 'uzum', 'behi', 'gilos', 'limon', 'banan', 'qovun', 'tarvuz', 'sabzi',
        'piyoz', 'bodom', 'kitob', 'qalam', 'daftar', 'maktab', 'ruchka', 'doska', 'quyosh', 'yulduz',
        'osmon', 'bulut', 'shamol', 'daryo', 'dengiz', 'bahor', 'qish', 'lola', 'daraxt', 'barg',
        'meva', 'asal', 'choy', 'shakar', 'tuxum', 'guruch', 'mushuk', 'kuchuk', 'quyon', 'tulki',
        'ayiq', 'sher', 'sigir', 'echki', 'tovuq', 'baliq', 'qush', 'lochin', 'burgut', 'tuya',
        'kiyik', 'zebra', 'maymun', 'delfin', 'kema', 'poyezd', 'eshik', 'stol', 'soat', 'radio',
        'kompas', 'xarita', 'globus', 'futbol', 'shahar', 'dala', 'olov', 'tosh', 'temir', 'oltin',
        'kumush', 'bayroq', 'yashil', 'qizil', 'sariq', 'qora', 'moviy', 'pushti', 'qozon', 'somsa',
        'palov', 'manti',
    ];

    /** Letters and digits for staff (teacher and admin) passwords, without the look-alikes 0 O 1 l I. */
    private const STAFF_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    private const CYRILLIC = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'j',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'x', 'ц' => 's',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'i', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya', 'ў' => 'o', 'қ' => 'q', 'ғ' => 'g', 'ҳ' => 'h',
    ];

    /**
     * A free login for a new user of $role. $name is the person's full name; for a branch admin
     * pass the branch name. Returns null when there is nothing to build it from.
     */
    public function login(Role $role, ?string $name, ?string $phone = null): ?string
    {
        $phone = $this->localPhone($phone);

        if ($role === Role::Student && $phone !== null) {
            return $this->firstFree($phone);
        }

        $base = $role === Role::BranchAdmin
            ? $this->slug((string) $name)
            : $this->nameSlug((string) $name);

        if ($base === '') {
            return null;
        }

        if ($role !== Role::Student) {
            return $this->firstFree($base);
        }

        // Students without a phone get two random digits, so classmates with the same name differ.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $email = $this->email($base.sprintf('%02d', random_int(0, 99)));

            if (! $this->taken($email)) {
                return $email;
            }
        }

        return $this->firstFree($base.sprintf('%02d', random_int(0, 99)));
    }

    public function password(Role $role): string
    {
        return match ($role) {
            Role::Student => $this->word().sprintf('%04d', random_int(0, 9999)),
            Role::Teacher, Role::SuperAdmin, Role::BranchAdmin => $this->staffPassword(),
        };
    }

    /**
     * Lowercase a-z and digits, words joined by dots: "O‘tkir G‘ulomov" → "otkir.gulomov".
     */
    public function slug(string $text): string
    {
        // Uzbek o‘ g‘ and the tutuq belgisi are written with many apostrophe look-alikes; they all go.
        $text = str_replace(['‘', '’', 'ʻ', 'ʼ', "'", '`', '´', 'ʹ'], '', mb_strtolower(trim($text)));
        $text = Str::ascii(strtr($text, self::CYRILLIC));
        $text = preg_replace('/[^a-z0-9]+/', '.', strtolower($text));

        return trim($text, '.');
    }

    /**
     * The 9-digit local number of an Uzbek phone (+998 removed), or null if it is not one.
     */
    public function localPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            $digits = substr($digits, 3);
        }

        return strlen($digits) === 9 ? $digits : null;
    }

    /**
     * ism.familiya from the first two words of the name.
     */
    private function nameSlug(string $name): string
    {
        $words = array_filter(explode('.', $this->slug($name)));

        return implode('.', array_slice($words, 0, 2));
    }

    private function firstFree(string $local): string
    {
        $email = $this->email($local);

        for ($n = 2; $this->taken($email); $n++) {
            $email = $this->email($local.$n);
        }

        return $email;
    }

    private function email(string $local): string
    {
        return $local.'@'.config('erp.login_domain');
    }

    private function taken(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    private function staffPassword(): string
    {
        $last = strlen(self::STAFF_ALPHABET) - 1;

        do {
            $password = '';

            for ($i = 0; $i < 10; $i++) {
                $password .= self::STAFF_ALPHABET[random_int(0, $last)];
            }
        } while (! preg_match('/[A-Z]/', $password) || ! preg_match('/[a-z]/', $password) || ! preg_match('/\d/', $password));

        return $password;
    }

    private function word(): string
    {
        return self::WORDS[random_int(0, count(self::WORDS) - 1)];
    }
}
