<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MultiAccountSession
{
    const COOKIE_NAME = 'accounts_switcher';
    const MAX_ACCOUNTS = 5;
    const IDLE_DAYS = 7;

    /**
     * Get all accounts from cookie
     */
    public function listAccounts(Request $request): array
    {
        $data = $this->readCookie($request);
        if (!$data) return [];

        // Filter expired entries (idle > 7 days)
        $accounts = array_filter($data['accounts'] ?? [], function ($account) {
            $lastUsed = \Carbon\Carbon::parse($account['last_used_at'] ?? now());
            return $lastUsed->diffInDays(now()) <= self::IDLE_DAYS;
        });

        // Validate sessions still exist in DB
        $validAccounts = [];
        foreach ($accounts as $account) {
            if ($this->isSessionValid($account['session_id'] ?? '')) {
                $validAccounts[] = $account;
            }
        }

        return array_values($validAccounts);
    }

    /**
     * Get active account ID
     */
    public function activeAccountId(Request $request): ?string
    {
        $data = $this->readCookie($request);
        return $data['active_id'] ?? null;
    }

    /**
     * Get active account data
     */
    public function activeAccount(Request $request): ?array
    {
        $activeId = $this->activeAccountId($request);
        if (!$activeId) return null;

        foreach ($this->listAccounts($request) as $account) {
            if ($account['id'] === $activeId) {
                return $account;
            }
        }
        return null;
    }

    /**
     * Add account to switcher after login
     */
    public function addAccount(User $user, Request $request): void
    {
        $data = $this->readCookie($request) ?? ['active_id' => null, 'accounts' => []];
        $accounts = $data['accounts'] ?? [];

        // Check duplicate
        foreach ($accounts as $account) {
            if ($account['user_id'] === $user->id) {
                // Update existing entry
                $accounts = array_map(function ($a) use ($user, $request) {
                    if ($a['user_id'] === $user->id) {
                        $a['session_id'] = $request->session()->getId();
                        $a['last_used_at'] = now()->toIso8601String();
                    }
                    return $a;
                }, $accounts);

                $data['accounts'] = array_values($accounts);
                $data['active_id'] = $this->findIdByUserId($accounts, $user->id);
                $this->writeCookie($request, $data);
                $this->log($user, 'login_added', $request);
                return;
            }
        }

        // Check max quota
        if (count($accounts) >= self::MAX_ACCOUNTS) {
            return; // Silently skip, UI should prevent this
        }

        $accountId = Str::uuid()->toString();
        $role = $this->getUserRole($user);

        $newEntry = [
            'id'           => $accountId,
            'user_id'      => $user->id,
            'session_id'   => $request->session()->getId(),
            'label'        => $this->getLabel($user),
            'role'         => $role,
            'email'        => $user->email,
            'last_used_at' => now()->toIso8601String(),
        ];

        $accounts[] = $newEntry;
        $data['accounts'] = $accounts;
        $data['active_id'] = $accountId;

        $this->writeCookie($request, $data);
        $this->log($user, 'login_added', $request);
    }

    /**
     * Switch to another account
     */
    public function switchTo(string $accountId, Request $request): ?User
    {
        $accounts = $this->listAccounts($request);
        $target = null;

        foreach ($accounts as $account) {
            if ($account['id'] === $accountId) {
                $target = $account;
                break;
            }
        }

        if (!$target) return null;

        // Validate session still valid
        if (!$this->isSessionValid($target['session_id'])) {
            $this->forget($accountId, $request);
            return null;
        }

        $user = User::find($target['user_id']);
        if (!$user || $user->status === 'suspended') {
            $this->forget($accountId, $request);
            return null;
        }

        // Log switch from current
        if ($currentUser = Auth::user()) {
            $this->log($currentUser, 'switched_from', $request, ['to' => $target['label']]);
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Login as target user
        Auth::login($user);

        // Update cookie
        $data = $this->readCookie($request) ?? ['active_id' => null, 'accounts' => []];
        $data['active_id'] = $accountId;
        $data['accounts'] = array_map(function ($a) use ($accountId, $request) {
            if ($a['id'] === $accountId) {
                $a['session_id'] = $request->session()->getId();
                $a['last_used_at'] = now()->toIso8601String();
            }
            return $a;
        }, $data['accounts']);

        $this->writeCookie($request, $data);
        $this->log($user, 'switched_to', $request);

        return $user;
    }

    /**
     * Forget a single account from switcher
     */
    public function forget(string $accountId, Request $request): void
    {
        $data = $this->readCookie($request);
        if (!$data) return;

        $removed = null;
        $data['accounts'] = array_values(array_filter($data['accounts'], function ($a) use ($accountId, &$removed) {
            if ($a['id'] === $accountId) {
                $removed = $a;
                return false;
            }
            return true;
        }));

        // If removed was active, set next active
        if ($data['active_id'] === $accountId) {
            $latest = $this->getLatestAccount($data['accounts']);
            $data['active_id'] = $latest ? $latest['id'] : null;
        }

        $this->writeCookie($request, $data);

        if ($removed && $user = Auth::user()) {
            $this->log($user, 'forgot', $request, ['forgotten' => $removed['label'] ?? '']);
        }
    }

    /**
     * Logout current account, switch to next if available
     */
    public function logoutCurrent(Request $request): ?array
    {
        $data = $this->readCookie($request);
        if (!$data) return null;

        $activeId = $data['active_id'];
        $user = Auth::user();

        // Remove active from list
        $data['accounts'] = array_values(array_filter($data['accounts'], fn ($a) => $a['id'] !== $activeId));

        // Find next account
        $next = $this->getLatestAccount($data['accounts']);
        $data['active_id'] = $next ? $next['id'] : null;

        $this->writeCookie($request, $data);

        if ($user) {
            $this->log($user, 'logged_out', $request);
        }

        return $next;
    }

    /**
     * Logout all accounts
     */
    public function logoutAll(Request $request): void
    {
        $user = Auth::user();
        if ($user) {
            $this->log($user, 'logout_all', $request);
        }

        // Invalidate all sessions
        $data = $this->readCookie($request);
        if ($data) {
            foreach ($data['accounts'] as $account) {
                DB::table('sessions')->where('id', $account['session_id'])->delete();
            }
        }

        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    /**
     * Count active accounts
     */
    public function count(Request $request): int
    {
        return count($this->listAccounts($request));
    }

    /**
     * Check if max quota reached
     */
    public function isMaxReached(Request $request): bool
    {
        return $this->count($request) >= self::MAX_ACCOUNTS;
    }

    // ─── Private Helpers ───────────────────────────────────────────────────────

    private function readCookie(Request $request): ?array
    {
        try {
            $raw = $request->cookie(self::COOKIE_NAME);
            if (!$raw) return null;
            return json_decode(Crypt::decryptString($raw), true);
        } catch (\Throwable) {
            return null;
        }
    }

    private function writeCookie(Request $request, array $data): void
    {
        $encrypted = Crypt::encryptString(json_encode($data));
        Cookie::queue(
            self::COOKIE_NAME,
            $encrypted,
            60 * 24 * self::IDLE_DAYS, // minutes
            '/',
            null,
            false,
            true, // httpOnly
            false,
            'lax'
        );
    }

    private function isSessionValid(string $sessionId): bool
    {
        if (empty($sessionId)) return false;
        return DB::table('sessions')->where('id', $sessionId)->exists();
    }

    private function getUserRole(User $user): string
    {
        if ($user->vendor) return 'vendor';
        if ($user->customer) return 'customer';
        return 'admin';
    }

    private function getLabel(User $user): string
    {
        if ($user->vendor) return $user->vendor->business_name;
        return $user->name;
    }

    private function getLatestAccount(array $accounts): ?array
    {
        if (empty($accounts)) return null;
        usort($accounts, fn ($a, $b) => strcmp($b['last_used_at'] ?? '', $a['last_used_at'] ?? ''));
        return $accounts[0];
    }

    private function findIdByUserId(array $accounts, int $userId): ?string
    {
        foreach ($accounts as $account) {
            if ($account['user_id'] === $userId) return $account['id'];
        }
        return null;
    }

    private function log(User $user, string $action, Request $request, array $context = []): void
    {
        try {
            DB::table('account_switcher_logs')->insert([
                'user_id'    => $user->id,
                'action'     => $action,
                'ip'         => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'context'    => json_encode($context) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Fail silently — don't break auth flow
        }
    }
}
