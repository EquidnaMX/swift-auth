<?php

/**
 * Implements SwiftAuth's session-backed authentication service helpers.
 */

namespace Equidna\SwiftAuth\Services;

use Carbon\CarbonImmutable;
use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Events\MfaChallengeStarted;
use Equidna\SwiftAuth\Events\SessionEvicted;
use Equidna\SwiftAuth\Events\UserLoggedIn;
use Equidna\SwiftAuth\Events\UserLoggedOut;
use Equidna\SwiftAuth\Models\RememberToken;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Models\UserSession;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Events\Dispatcher;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use SessionHandlerInterface;

/**
 * Handles login, logout, session checks, and remember-me flows using Laravel's session storage.
 */
class SwiftSessionAuth
{
    protected Session $session;
    protected string $sessionKey = 'swift_auth_user_id';
    protected string $sessionUidKey = 'swift_auth_session_id';
    protected string $createdAtKey = 'swift_auth_created_at';
    protected string $lastActivityKey = 'swift_auth_last_activity';
    protected string $absoluteExpiryKey = 'swift_auth_absolute_expires_at';
    protected string $rememberCookieName;
    protected string $pendingMfaUserKey = 'swift_auth_pending_mfa_user_id';
    protected string $pendingMfaDriverKey = 'swift_auth_pending_mfa_driver';
    private ?User $cachedUser = null;

    public function __construct(
        Session $session,
        private UserRepositoryInterface $userRepository,
        private Dispatcher $events
    ) {
        $this->session = $session;
        $this->rememberCookieName = (string) $this->getConfig(
            'swift-auth.remember_me.cookie_name',
            'swift_auth_remember'
        );
    }

    /**
     * Logs in a user by storing their ID in the session.
     *
     * @return array{evicted_session_ids: array<int, string>}
     */
    public function login(
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null,
        bool $remember = false,
    ): array {
        $this->session->regenerate(true);

        $this->cachedUser = $user;

        $now = CarbonImmutable::now();
        $sessionId = $this->session->getId();
        $absoluteExpiry = $this->getAbsoluteExpiry($now);

        $this->session->forget($this->pendingMfaUserKey);
        $this->session->forget($this->pendingMfaDriverKey);

        $this->session->put($this->sessionKey, $user->getKey());
        $this->session->put($this->sessionUidKey, $sessionId);
        $this->session->put($this->createdAtKey, $now->toIso8601String());
        $this->session->put($this->lastActivityKey, $now->toIso8601String());

        if ($absoluteExpiry !== null) {
            $this->session->put($this->absoluteExpiryKey, $absoluteExpiry->toIso8601String());
        }

        $metadata = $this->extractAgentMetadata($userAgent);

        $this->recordUserSession(
            user: $user,
            sessionId: $sessionId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            deviceName: $deviceName,
            platform: $metadata['platform'],
            browser: $metadata['browser'],
            lastActivity: $now,
        );

        $evicted = $this->enforceSessionLimit(
            user: $user,
            currentSessionId: $sessionId,
        );

        if ($remember && $this->shouldIssueRememberToken()) {
            $this->queueRememberToken(
                user: $user,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                deviceName: $deviceName,
                platform: $metadata['platform'],
                browser: $metadata['browser'],
            );
        }

        $this->dispatchEvent(new UserLoggedIn(
            $user->getKey(),
            $sessionId,
            $ipAddress,
            $this->getDriverMetadata()
        ));

        return [
            'evicted_session_ids' => $evicted,
        ];
    }

    /**
     * Logs out the user by removing their ID from the session.
     */
    public function logout(): void
    {
        $sessionId = (string) $this->session->get($this->sessionUidKey);
        $rememberValue = Cookie::get($this->rememberCookieName);
        $userId = $this->session->get($this->sessionKey);

        $this->cachedUser = null;

        $this->session->forget($this->sessionKey);
        $this->session->forget($this->sessionUidKey);
        $this->session->forget($this->createdAtKey);
        $this->session->forget($this->lastActivityKey);
        $this->session->forget($this->absoluteExpiryKey);
        $this->session->forget($this->pendingMfaUserKey);
        $this->session->forget($this->pendingMfaDriverKey);

        if ($sessionId !== '') {
            $this->deleteUserSession($sessionId);
        }

        if (is_string($rememberValue)) {
            $this->deleteRememberToken($rememberValue);
        }

        $this->forgetRememberCookie();

        $this->session->invalidate();
        $this->session->regenerate(true);

        $this->dispatchEvent(new UserLoggedOut(
            $userId,
            $sessionId,
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Records a pending MFA challenge without completing login.
     */
    public function startMfaChallenge(
        User $user,
        string $driver = 'otp',
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $this->session->put($this->pendingMfaUserKey, $user->getKey());
        $this->session->put($this->pendingMfaDriverKey, $driver);

        $this->dispatchEvent(new MfaChallengeStarted(
            $user->getKey(),
            $this->getSessionId(),
            $ipAddress,
            $this->getDriverMetadata()
        ));

        logger()->info('swift-auth.mfa.challenge_started', [
            'user_id' => $user->getKey(),
            'driver' => $driver,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Determines whether the current authenticated user may perform one or more actions.
     *
     * @param  string|array<string> $actions  Single action or list of actions to check.
     * @return bool                           True if allowed.
     */
    public function canPerformAction(string|array $actions): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Allow super-admin shortcut
        if ($user->hasRole('sw-admin')) {
            return true;
        }

        $required = (array) $actions;
        $available = $user->availableActions();

        foreach ($required as $r) {
            if (in_array($r, $available, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if a user is currently authenticated via session.
     */
    public function check(): bool
    {
        $this->cachedUser = null;

        $id = $this->id();

        if (!$id && !$this->attemptRememberLogin()) {
            return false;
        }

        $userId = $this->id();
        $sessionId = (string) $this->session->get($this->sessionUidKey);

        if ($sessionId === '' || !$this->sessionRecordExists($sessionId)) {
            $this->logout();

            return false;
        }

        if (!$userId) {
            $this->logout();

            return false;
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            $this->logout();

            return false;
        }

        if ($this->isExpired()) {
            $this->logout();

            return false;
        }

        $this->cachedUser = $user;
        $this->touchLastActivity();

        return true;
    }

    public function id(): ?int
    {
        $val = $this->session->get($this->sessionKey);

        if ($val === null) {
            return null;
        }

        if (is_int($val)) {
            return $val;
        }

        if (is_string($val) && ctype_digit($val)) {
            return (int) $val;
        }

        return null;
    }

    public function user(): ?User
    {
        if (!$this->check()) {
            return null;
        }

        return $this->cachedUser;
    }

    /**
     * @throws ModelNotFoundException
     */
    public function userOrFail(): User
    {
        $id = $this->id();

        if (!$id || !($user = $this->userRepository->findById($id))) {
            throw new ModelNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UserSession>
     */
    public function sessionsForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::query()
            ->where('id_user', $userId)
            ->orderByDesc('last_activity')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UserSession>
     */
    public function allSessions(): \Illuminate\Database\Eloquent\Collection
    {
        return UserSession::query()
            ->orderByDesc('last_activity')
            ->get();
    }

    public function revokeSession(int $userId, string $sessionId): void
    {
        UserSession::query()
            ->where('id_user', $userId)
            ->where('session_id', $sessionId)
            ->delete();

        if ($this->session->getId() === $sessionId) {
            $this->logout();
        }
    }

    /**
     * @return array{deleted_sessions:int, cleared_remember_tokens:int}
     */
    public function revokeAllSessionsForUser(
        int $userId,
        bool $includeRememberTokens = false,
    ): array {
        $deleted = UserSession::query()
            ->where('id_user', $userId)
            ->delete();

        $activeSessionUserId = (int) $this->session->get($this->sessionKey);

        if ($activeSessionUserId === $userId) {
            $this->logout();
        }

        $clearedRememberTokens = $includeRememberTokens
            ? $this->revokeRememberTokensForUser($userId)
            : 0;

        return [
            'deleted_sessions' => $deleted,
            'cleared_remember_tokens' => $clearedRememberTokens,
        ];
    }

    public function revokeRememberTokensForUser(int $userId): int
    {
        return RememberToken::query()
            ->where('id_user', $userId)
            ->delete();
    }

    /**
     * Determines whether the current authenticated user has one or more roles.
     *
     * @param  string|array<string> $roles  Role name or list of role names to check.
     * @return bool                         True if user has at least one of the roles.
     */
    public function hasRole(string|array $roles): bool
    {
        if (!$this->check()) {
            return false;
        }

        $user = $this->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole($roles);
    }

    private function isExpired(): bool
    {
        $now = CarbonImmutable::now();
        $lastActivity = $this->parseTimestamp((string) $this->session->get($this->lastActivityKey));

        $idleTimeout = $this->getConfig(
            'swift-auth.session_lifetimes.idle_timeout_seconds',
            null
        );

        if ($idleTimeout && $lastActivity && $now->diffInSeconds($lastActivity) > $idleTimeout) {
            return true;
        }

        $absoluteExpiry = $this->parseTimestamp((string) $this->session->get($this->absoluteExpiryKey));

        return $absoluteExpiry !== null && $now->greaterThan($absoluteExpiry);
    }

    private function sessionRecordExists(string $sessionId): bool
    {
        try {
            return UserSession::query()
                ->where('session_id', $sessionId)
                ->exists();
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.session.validation_failed', [
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function touchLastActivity(): void
    {
        $now = CarbonImmutable::now();
        $sessionId = (string) $this->session->get($this->sessionUidKey);

        $this->session->put($this->lastActivityKey, $now->toIso8601String());

        try {
            if ($sessionId) {
                UserSession::query()
                    ->where('session_id', $sessionId)
                    ->update([
                        'last_activity' => $now,
                    ]);
            }
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.session.touch_failed', [
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function getAbsoluteExpiry(CarbonImmutable $now): ?CarbonImmutable
    {
        $absoluteTtl = $this->getConfig(
            'swift-auth.session_lifetimes.absolute_timeout_seconds',
            null
        );

        return $absoluteTtl ? $now->addSeconds($absoluteTtl) : null;
    }

    private function attemptRememberLogin(): bool
    {
        if (!$this->shouldIssueRememberToken()) {
            return false;
        }

        $cookie = Cookie::get($this->rememberCookieName);

        if (!is_string($cookie) || $cookie === '') {
            return false;
        }

        [$selector, $validator] = $this->splitRememberCookie($cookie);

        if ($selector === null || $validator === null) {
            $this->forgetRememberCookie();

            return false;
        }

        try {
            $token = RememberToken::query()
                ->where('selector', $selector)
                ->first();
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.remember.load_failed', [
                'selector' => $selector,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (!$token || $this->rememberTokenExpired($token)) {
            $token?->delete();
            $this->forgetRememberCookie();

            return false;
        }

        $expected = hash('sha256', $validator);

        if (!hash_equals($token->hashed_token, $expected)) {
            $token->delete();
            $this->forgetRememberCookie();

            return false;
        }

        $user = $this->userRepository->findById((int) $token->id_user);

        if (!$user) {
            $token->delete();
            $this->forgetRememberCookie();

            return false;
        }

        $shouldRotate = (bool) $this->getConfig(
            'swift-auth.remember_me.rotate_on_use',
            true,
        );

        if ($shouldRotate) {
            $token->delete();
        } else {
            $token->last_used_at = CarbonImmutable::now();
            $token->save();
        }

        $request = function_exists('request') ? request() : null;
        $ip = $request && method_exists($request, 'ip') ? $request->ip() : null;
        $userAgent = $request && method_exists($request, 'userAgent') ? $request->userAgent() : null;

        $this->login(
            user: $user,
            ipAddress: $ip,
            userAgent: $userAgent,
            deviceName: $this->resolveDeviceNameFromRequest(),
            remember: $shouldRotate,
        );

        return true;
    }

    private function queueRememberToken(
        User $user,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
        ?string $platform,
        ?string $browser,
    ): void {
        try {
            $selector = Str::random(20);
            $validator = Str::random(64);
            $hashedValidator = hash('sha256', $validator);
            $ttlSeconds = (int) $this->getConfig('swift-auth.remember_me.ttl_seconds', 1209600);
            $expiresAt = CarbonImmutable::now()->addSeconds($ttlSeconds);

            RememberToken::query()->create(
                [
                    'id_user' => $user->getKey(),
                    'selector' => $selector,
                    'hashed_token' => $hashedValidator,
                    'expires_at' => $expiresAt,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'device_name' => $deviceName,
                    'platform' => $platform,
                    'browser' => $browser,
                ],
            );

            $cookieValue = $selector . ':' . $validator;
            $minutes = (int) ceil($ttlSeconds / 60);

            $cookie = Cookie::make(
                $this->rememberCookieName,
                $cookieValue,
                minutes: $minutes,
                path: (string) $this->getConfig('swift-auth.remember_me.path', '/'),
                domain: $this->getConfig('swift-auth.remember_me.domain', null),
                secure: (bool) $this->getConfig('swift-auth.remember_me.secure', true),
                httpOnly: true,
                raw: false,
                sameSite: (string) $this->getConfig('swift-auth.remember_me.same_site', 'lax'),
            );

            Cookie::queue($cookie);
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.remember.issue_failed', [
                'user_id' => $user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function deleteRememberToken(string $cookieValue): void
    {
        [$selector, $validator] = $this->splitRememberCookie($cookieValue);

        if ($selector === null || $validator === null) {
            $this->forgetRememberCookie();

            return;
        }

        try {
            RememberToken::query()
                ->where('selector', $selector)
                ->delete();
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.remember.delete_failed', [
                'selector' => $selector,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->forgetRememberCookie();
    }

    private function forgetRememberCookie(): void
    {
        Cookie::queue(Cookie::forget($this->rememberCookieName));
    }

    private function shouldIssueRememberToken(): bool
    {
        return (bool) $this->getConfig('swift-auth.remember_me.enabled', true);
    }

    private function rememberTokenExpired(RememberToken $token): bool
    {
        return $token->expires_at !== null
            && CarbonImmutable::now()->greaterThan(CarbonImmutable::parse($token->expires_at));
    }

    /**
     * @return array{0:null|string,1:null|string}
     */
    private function splitRememberCookie(string $cookieValue): array
    {
        $parts = explode(':', $cookieValue, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private function resolveDeviceNameFromRequest(): ?string
    {
        try {
            $request = request();

            return method_exists($request, 'header')
                ? (string) $request->header('X-Device-Name', '')
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{platform:null|string,browser:null|string}
     */
    private function extractAgentMetadata(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'platform' => null,
                'browser' => null,
            ];
        }

        $platform = null;
        $browser = null;

        $knownPlatforms = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os x/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i',
        ];

        foreach ($knownPlatforms as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $platform = $name;
                break;
            }
        }

        $knownBrowsers = [
            'Chrome' => '/chrome|crios/i',
            'Firefox' => '/firefox|fennec/i',
            'Safari' => '/safari/i',
            'Edge' => '/edg/i',
            'Opera' => '/opera|opr\//i',
        ];

        foreach ($knownBrowsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $browser = $name;
                break;
            }
        }

        return [
            'platform' => $platform,
            'browser' => $browser,
        ];
    }

    private function recordUserSession(
        User $user,
        string $sessionId,
        ?string $ipAddress,
        ?string $userAgent,
        ?string $deviceName,
        ?string $platform,
        ?string $browser,
        CarbonImmutable $lastActivity,
    ): void {
        try {
            // Use the query builder form so static analyzers understand the
            // Eloquent builder methods are being used.
            UserSession::query()->updateOrCreate(
                [
                    'session_id' => $sessionId,
                ],
                [
                    'id_user' => $user->getKey(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'device_name' => $deviceName,
                    'platform' => $platform,
                    'browser' => $browser,
                    'last_activity' => $lastActivity,
                ],
            );
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.session.record_failed', [
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function enforceSessionLimit(
        User $user,
        string $currentSessionId,
    ): array {
        $maxSessions = $this->getConfig(
            'swift-auth.session_limits.max_sessions',
            null,
        );

        if ($maxSessions === null) {
            return [];
        }

        $maxSessions = (int) $maxSessions;

        if ($maxSessions <= 0) {
            return [];
        }

        $policy = (string) $this->getConfig(
            'swift-auth.session_limits.eviction',
            'oldest',
        );

        try {
            $sessions = UserSession::query()
                ->where('id_user', $user->getKey())
                ->orderByDesc('last_activity')
                ->get();

            if ($sessions->count() <= $maxSessions) {
                return [];
            }

            $overflow = $sessions->count() - $maxSessions;

            $sessionsToDelete = $policy === 'newest'
                ? $sessions->take($overflow)
                : $sessions->slice($maxSessions, $overflow);

            $sessionIds = $sessionsToDelete
                ->pluck('session_id')
                ->all();

            UserSession::query()
                ->whereIn('session_id', $sessionIds)
                ->delete();

            foreach ($sessionIds as $evictedSessionId) {
                $this->dispatchEvent(new SessionEvicted(
                    $user->getKey(),
                    $evictedSessionId,
                    $this->getClientIp(),
                    $this->getDriverMetadata()
                ));
            }

            if (in_array($currentSessionId, $sessionIds, true)) {
                $this->logout();
            }

            logger()->info('swift-auth.session.evicted', [
                'user_id' => $user->getKey(),
                'evicted_sessions' => $sessionIds,
                'policy' => $policy,
            ]);

            return $sessionIds;
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.session.limit_enforce_failed', [
                'user_id' => $user->getKey(),
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function deleteUserSession(string $sessionId): void
    {
        try {
            UserSession::query()
                ->where('session_id', $sessionId)
                ->delete();
        } catch (\Throwable $exception) {
            logger()->warning('swift-auth.session.delete_failed', [
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function parseTimestamp(string $timestamp): ?CarbonImmutable
    {
        if ($timestamp === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($timestamp);
        } catch (\Throwable) {
            return null;
        }
    }

    private function getConfig(string $key, mixed $default): mixed
    {
        try {
            return config($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Dispatches events via the configured dispatcher.
     */
    private function dispatchEvent(object $event): void
    {
        $this->events->dispatch($event);
    }

    /**
     * @return array<string, string>
     */
    private function getDriverMetadata(): array
    {
        $handler = $this->session->getHandler();

        return [
            'handler' => $handler instanceof SessionHandlerInterface ? SessionHandlerInterface::class : (string) $handler,
            'name' => $this->session->getName(),
            'store' => $this->session::class,
        ];
    }

    private function getSessionId(): string
    {
        return (string) $this->session->getId();
    }

    private function getClientIp(): ?string
    {
        if (function_exists('request') && request()) {
            return request()->ip();
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
