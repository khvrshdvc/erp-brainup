<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\ShopOrderStatus;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'must_change_password',
        'role',
        'status',
        'branch_id',
        'birth_date',
        'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    private ?array $availableBranchIds = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'role' => Role::class,
            'status' => UserStatus::class,
            'birth_date' => 'date',
            'xp' => 'integer',
            'streak' => 'integer',
        ];
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn () => collect(preg_split('/\s+/', trim($this->name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('')
        );
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isBranchAdmin(): bool
    {
        return $this->role === Role::BranchAdmin;
    }

    public function isTeacher(): bool
    {
        return $this->role === Role::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    /**
     * Memoized: controllers, policies, form requests and the layout all ask for it within one request.
     * Call forgetAvailableBranchIds() after changing the user's branches inside that request.
     */
    public function availableBranchIds(): array
    {
        return $this->availableBranchIds ??= $this->resolveAvailableBranchIds();
    }

    public function forgetAvailableBranchIds(): void
    {
        $this->availableBranchIds = null;
    }

    /**
     * The super admin still reaches deactivated branches; everyone else only works in active ones.
     */
    private function resolveAvailableBranchIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Branch::query()->pluck('id')->all();
        }

        if ($this->isTeacher()) {
            return $this->branches()->active()->pluck('branches.id')->all();
        }

        return $this->branch_id && Branch::whereKey($this->branch_id)->active()->exists() ? [(int) $this->branch_id] : [];
    }

    /**
     * Every branch the user belongs to has been deactivated (EnsureUserIsActive signs them out).
     */
    public function hasOnlyInactiveBranches(): bool
    {
        if ($this->isSuperAdmin() || $this->availableBranchIds() !== []) {
            return false;
        }

        return $this->isTeacher() ? $this->branches()->exists() : $this->branch_id !== null;
    }

    /**
     * The topbar shows this on every page, so it is cached briefly and dropped whenever
     * a notification is sent or read (see ForgetUnreadNotificationCount).
     */
    public function unreadNotificationsCount(): int
    {
        return Cache::remember(static::unreadCountCacheKey($this->id), 300, fn () => $this->unreadNotifications()->count());
    }

    public static function forgetUnreadNotificationsCount(int|string $userId): void
    {
        Cache::forget(static::unreadCountCacheKey($userId));
    }

    private static function unreadCountCacheKey(int|string $userId): string
    {
        return 'unread-notifications:'.$userId;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class, 'group_student', 'student_id', 'study_group_id')
            ->withPivot('joined_at');
    }

    public function teachingGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class, 'teacher_id');
    }

    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withPivot('awarded_at');
    }

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    /**
     * BrainCoins the student can still spend: everything earned (users.xp, which also
     * drives levels and the leaderboard) minus pending and delivered shop orders.
     */
    public function coinBalance(): int
    {
        $spent = (int) ShopOrder::withoutGlobalScopes()
            ->where('user_id', $this->id)
            ->whereIn('status', ShopOrderStatus::spendingValues())
            ->sum('price');

        return max(0, (int) $this->xp - $spent);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function scopeOfRole(Builder $query, Role $role): Builder
    {
        return $query->where('users.role', $role->value);
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $this->scopeOfRole($query, Role::Student);
    }

    public function scopeTeachers(Builder $query): Builder
    {
        return $this->scopeOfRole($query, Role::Teacher);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('users.status', UserStatus::Active->value);
    }

    public function scopeInBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('users.branch_id', $branchId);
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderByDesc('users.xp')->orderBy('users.id');
    }
}
