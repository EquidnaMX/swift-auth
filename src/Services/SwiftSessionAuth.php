<?php

/**
 * Implements SwiftAuth's session-backed authentication service helpers.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Services;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Str;

use Carbon\CarbonImmutable;

use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Models\RememberToken;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Models\UserSession;

/**
 * Provides session-based authentication handling for users.
 *
 * Handles login, logout, session checks, and user retrieval using Laravel's session storage.
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
    private null|User $cachedUser = null;

    /**
     * Creates a new SwiftSessionAuth instance.
     *
     * @param  Session                  $session         Laravel session store instance.
     * @param  UserRepositoryInterface  $userRepository  User data access layer.
     * @param  Dispatcher               $events          Event dispatcher instance.
     */
    public function __construct(
        Session $session,
        private UserRepositoryInterface $userRepository,
        private Dispatcher $events
    ) {
        $this->session = $session;
        $this->rememberCookieName = (string) $this->getConfig(
            'swift-auth.remember_me.cookie_name',
            'swift_auth_remember',
        );
    }

    /**
     * Logs in a user by storing their ID in the session.
     *
     * @param  User $user  User instance to authenticate.
     * @return array{evicted_session_ids: array<int, string>}
     */
    public function login(
        User $user,
        null|string $ipAddress = null,
        null|string $userAgent = null,
        null|string $deviceName = null,
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

        return [
            'evicted_session_ids' => $evicted,
        ];
    }

    /**
     * Logs out the user by removing their ID from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $sessionId = $this->session->get($this->sessionUidKey);
        $rememberValue = Cookie::get($this->rememberCookieName);

        $this->cachedUser = null;

        $this->session->forget($this->sessionKey);
        $this->session->forget($this->sessionUidKey);
        $this->session->forget($this->createdAtKey);
        $this->session->forget($this->lastActivityKey);
        $this->session->forget($this->absoluteExpiryKey);
        $this->session->forget($this->pendingMfaUserKey);
        $this->session->forget($this->pendingMfaDriverKey);

        if ($sessionId) {
            $this->deleteUserSession($sessionId);
        }

        if (is_string($rememberValue)) {
            $this->deleteRememberToken($rememberValue);
        }

        $this->forgetRememberCookie();

        $this->session->invalidate();
        $this->session->regenerate(true);
    }

    /**
     * Records a pending MFA challenge without completing login.
     *
     * @param  User        $user       Authenticated user awaiting MFA.
     * @param  string      $driver     MFA driver identifier (otp/webauthn).
     * @param  null|string $ipAddress  Request IP address.
     * @param  null|string $userAgent  Request user agent string.
     * @return void
     */
    public function startMfaChallenge(
        User $user,
        string $driver,
        null|string $ipAddress = null,
        null|string $userAgent = null,
    ): void {
        $this->session->put($this->pendingMfaUserKey, $user->getKey());
        $this->session->put($this->pendingMfaDriverKey, $driver);

        logger()->info('swift-auth.mfa.challenge_started', [
            'user_id' => $user->getKey(),
            'driver' => $driver,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Determines if a user is currently authenticated via session.
     *
     * @return bool  True if authenticated, false otherwise.
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

    /**
     * Returns the authenticated user's ID from the session.
     *
     * @return int|null  User ID or null if not authenticated.
     */
    public function id(): null|int
    {
        return $this->session->get($this->sessionKey);
    }

    /**
     * Returns the authenticated User model instance, or null if not found.
     *
     * @return User|null  Authenticated user or null.
     */
    public function user(): null|User
    {
        if (!$this->check()) {
            return null;
        }

        return $this->cachedUser;
    }

    /**
     * Returns the authenticated User model instance or throws exception if not found.
     *
     * @return User                    Authenticated user instance.
     * @throws ModelNotFoundException  When user ID not in session or user record not found.
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
     * Returns all tracked sessions for the given user.
     *
     * @param  int $userId  Identifier of the user to inspect.
     * @return \Illuminate\Support\Collection<int, UserSession>
     */
    public function sessionsForUser(int $userId): \Illuminate\Support\Collection
    {
        return UserSession::query()
            ->where('id_user', $userId)
            ->orderByDesc('last_activity')
            ->get();
    }

    /**
     * Returns all tracked sessions across all users.
     *
     * @return \Illuminate\Support\Collection<int, UserSession>
     */
    public function allSessions(): \Illuminate\Support\Collection
    {
        return UserSession::query()
            ->orderByDesc('last_activity')
            ->get();
    }

    /**
     * Revokes a specific session owned by the given user.
     *
     * @param  int    $userId     Identifier of the user who owns the session.
     * @param  string $sessionId  Session identifier to revoke.
     * @return void
     */
    public function revokeSession(
        int $userId,
        string $sessionId,
    ): void {
        UserSession::query()
            ->where('id_user', $userId)
            ->where('session_id', $sessionId)
            ->delete();

        if ($this->session->getId() === $sessionId) {
            $this->logout();
        }
    }

    /**
     * Revokes all sessions for a given user, optionally clearing remember-me tokens.
     *
     * @param  int  $userId                 Identifier of the user whose sessions will be removed.
     * @param  bool $includeRememberTokens  Whether to also delete remember-me tokens for the user.
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

    /**
     * Deletes all remember-me tokens for the given user.
     *
     * @param  int $userId  Identifier of the user whose tokens will be deleted.
     * @return int          Count of deleted tokens.
     */
    public function revokeRememberTokensForUser(int $userId): int
    {
        return RememberToken::query()
            ->where('id_user', $userId)
            ->delete();
    }

    /**
     * Determines whether the current session has exceeded idle or absolute limits.
     *
     * @return bool
     */
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

    /**
     * Validates that the current session exists in the persistence store.
     *
     * @param  string $sessionId  Identifier to verify.
     * @return bool
     */
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

    /**
     * Updates the last-activity timestamp for the current session.
     *
     * @return void
     */
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

    /**
     * Computes the absolute expiration timestamp for a session.
     *
     * @param  CarbonImmutable $now  The moment the session was issued.
     * @return CarbonImmutable|null
     */
    private function getAbsoluteExpiry(CarbonImmutable $now): null|CarbonImmutable
    {
        $absoluteTtl = $this->getConfig(
            'swift-auth.session_lifetimes.absolute_timeout_seconds',
            null
        );

        return $absoluteTtl ? $now->addSeconds($absoluteTtl) : null;
    }

    /**
     * Attempts to authenticate using a persistent remember-me cookie.
     *
     * @return bool
     */
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

        $request = request();
        $ip = method_exists($request, 'ip') ? $request->ip() : null;
        $userAgent = method_exists($request, 'userAgent') ? $request->userAgent() : null;

        $this->login(
            user: $user,
            ipAddress: $ip,
            userAgent: $userAgent,
            deviceName: $this->resolveDeviceNameFromRequest(),
            remember: $shouldRotate,
        );

        return true;
    }

    /**
     * Persists a remember-me token and queues the cookie on the response.
     *
     * @param  User        $user        Authenticated user.
     * @param  null|string $ipAddress   Request IP address.
     * @param  null|string $userAgent   Request user agent string.
     * @param  null|string $deviceName  Device name.
     * @param  null|string $platform    Parsed platform name.
     * @param  null|string $browser     Parsed browser name.
     * @return void
     */
    private function queueRememberToken(
        User $user,
        null|string $ipAddress,
        null|string $userAgent,
        null|string $deviceName,
        null|string $platform,
        null|string $browser,
    ): void {
        try {
            $selector = Str::random(20);
            $validator = Str::random(64);
            $hashedValidator = hash('sha256', $validator);
            $expiresAt = CarbonImmutable::now()->addSeconds(
                (int) $this->getConfig('swift-auth.remember_me.ttl_seconds', 1209600),
            );

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
            $minutes = (int) ceil(
                (int) $this->getConfig('swift-auth.remember_me.ttl_seconds', 1209600) / 60,
            );

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

    /**
     * Deletes a remember-me token associated with the given cookie value.
     *
     * @param  string $cookieValue  Raw cookie value containing selector and validator.
     * @return void
     */
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

    /**
     * Removes the remember-me cookie from the response queue.
     *
     * @return void
     */
    private function forgetRememberCookie(): void
    {
        Cookie::queue(Cookie::forget($this->rememberCookieName));
    }

    /**
     * Checks if remember-me functionality is enabled.
     *
     * @return bool
     */
    private function shouldIssueRememberToken(): bool
    {
        return (bool) $this->getConfig('swift-auth.remember_me.enabled', true);
    }

    /**
     * Determines whether the given remember token has expired.
     *
     * @param  RememberToken $token  Token to check.
     * @return bool
     */
    private function rememberTokenExpired(RememberToken $token): bool
    {
        return $token->expires_at !== null
            && CarbonImmutable::now()->greaterThan(CarbonImmutable::parse($token->expires_at));
    }

    /**
     * Extracts selector and validator segments from a cookie value.
     *
     * @param  string $cookieValue  Raw cookie value.
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

    /**
     * Derives a device name from the current request if available.
     *
     * @return string|null
     */
    private function resolveDeviceNameFromRequest(): null|string
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
     * Extracts coarse device metadata from a user agent string.
     *
     * @param  null|string $userAgent  Raw user agent string.
     * @return array{platform:null|string,browser:null|string}
     */
    private function extractAgentMetadata(null|string $userAgent): array
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

    /**
     * Records a session row for the authenticated user.
     *
     * @param  User             $user          The authenticated user.
     * @param  string           $sessionId     Unique session identifier.
     * @param  null|string      $ipAddress     Request IP address.
     * @param  null|string      $userAgent     Request user agent string.
     * @param  null|string      $deviceName    Device name hint.
     * @param  CarbonImmutable  $lastActivity  Timestamp of last activity.
     * @return void
     */
    private function recordUserSession(
        User $user,
        string $sessionId,
        null|string $ipAddress,
        null|string $userAgent,
        null|string $deviceName,
        null|string $platform,
        null|string $browser,
        CarbonImmutable $lastActivity,
    ): void {
        try {
            UserSession::updateOrCreate(
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
     * Enforces a per-user session limit by evicting old or new sessions.
     *
     * @param  User   $user              Authenticated user.
     * @param  string $currentSessionId  Newly issued session identifier.
     * @return array<int, string>
     */
    private function enforceSessionLimit(
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

    /**
     * Deletes a persisted session record when logging out.
     *
     * @param  string $sessionId  Identifier to remove.
     * @return void
     */
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

    /**
     * Parses a stored timestamp string into a Carbon instance.
     *
     * @param  string $timestamp  Serialized timestamp from the session store.
     * @return CarbonImmutable|null
     */
    private function parseTimestamp(string $timestamp): null|CarbonImmutable
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

    /**
     * Retrieves a configuration value with a default fallback when configuration helpers are unavailable.
     *
     * @param  string $key      Configuration path.
     * @param  mixed  $default  Default fallback.
     * @return mixed
     */
    private function getConfig(
        string $key,
        mixed $default,
    ): mixed {
        try {
            return config($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Checks if the authenticated user is allowed to perform the given action(s).
     *
     * @param  string|array<string,mixed> $actions  Action or list of actions to validate.
     * @return bool                                  True if user has permission for at least one action.
     */
    public function canPerformAction(string|array $actions): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        $available = $user->availableActions();

        if (in_array('sw-admin', $available)) {
            return true;
        }

        return is_array($actions)
            ? count(array_intersect($available, $actions)) > 0
            : in_array($actions, $available);
    }

    /**
     * Checks if the authenticated user has any of the given roles.
     *
     * @param  string|array<string,mixed> $roles  Role name(s) to check.
     * @return bool                                True if user has at least one role.
     */
    public function hasRole(string|array $roles): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // sw-admin action implies all role checks pass
        if ($this->canPerformAction('sw-admin')) {
            return true;
        }

        return $user->hasRoles($roles);
    }

    /**
     * Dispatches a session eviction event when a session limit is enforced.
     *
     * @param  User   $user             User whose sessions are being limited.
     * @param  string $evictedSessionId Identifier of the evicted session.
     * @return void
     */
    public function enforceSessionLimit(User $user, string $evictedSessionId): void
    {
        $this->dispatchEvent(new SessionEvicted(
            $user->getKey(),
            $evictedSessionId,
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Dispatches an event indicating a multi-factor authentication challenge has started.
     *
     * @param  User $user  User undergoing MFA challenge.
     * @return void
     */
    public function startMfaChallenge(User $user): void
    {
        $this->dispatchEvent(new MfaChallengeStarted(
            $user->getKey(),
            $this->getSessionId(),
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Dispatches events via the configured dispatcher.
     *
     * @param  object $event  Event instance to dispatch.
     * @return void
     */
    private function dispatchEvent(object $event): void
    {
        $this->events->dispatch($event);
    }

    /**
     * Returns metadata about the session storage driver and handler.
     *
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

    /**
     * Returns the current session identifier.
     *
     * @return string
     */
    private function getSessionId(): string
    {
        return (string) $this->session->getId();
    }

    /**
     * Attempts to determine the client's IP address.
     *
     * @return string|null
     */
    private function getClientIp(): ?string
    {
        if (function_exists('request') && request()) {
            return request()->ip();
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
     * Attempts to reauthenticate a user using a remember-me cookie.
     */
    protected function attemptRememberReauthentication(CarbonImmutable $now): bool
    {
        if (!config('swift-auth.session.remember_me.enabled', true)) {
            return false;
        }

        $rememberValue = Cookie::get($this->rememberCookie);

        if (!$rememberValue || !str_contains($rememberValue, '|')) {
            return false;
        }

        [$userId, $token, $expires] = array_pad(explode('|', $rememberValue, 3), 3, null);

        if (!$userId || !$token || !$expires) {
            $this->clearRememberCookie();
            return false;
        }

        if ($now->getTimestamp() >= (int) $expires) {
            $this->purgeRememberToken((int) $userId);
            return false;
        }

        $user = $this->userRepository->findById((int) $userId);

        if (!$user || !hash_equals((string) $user->remember_token, hash('sha256', $token))) {
            $this->purgeRememberToken((int) $userId);
            return false;
        }

        $this->login($user, false);

        if (config('swift-auth.session.remember_me.rotate', true)) {
            $this->createRememberToken($user, $now);
        }

        return true;
    }

    /**
     * Creates and queues a remember-me token and cookie for the user.
     */
    protected function createRememberToken(User $user, CarbonImmutable $now): void
    {
        $ttl = (int) config('swift-auth.session.remember_me.ttl', 60 * 60 * 24 * 14);
        $token = Str::random(60);
        $hashedToken = hash('sha256', $token);
        $user->remember_token = $hashedToken;
        $user->save();

        $expiresAt = $now->addSeconds($ttl);
        $minutes = max(1, (int) ceil($ttl / 60));

        Cookie::queue(cookie(
            $this->rememberCookie,
            implode('|', [$user->getKey(), $token, $expiresAt->getTimestamp()]),
            $minutes,
            null,
            null,
            false,
            true,
            false,
            'Strict',
        ));
    }

    /**
     * Clears remember-me state for a user and cookie.
     */
    protected function purgeRememberToken(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user) {
            $user->remember_token = null;
            $user->save();
        }

        $this->clearRememberCookie();
    }

    /**
     * Queues removal of the remember-me cookie.
     */
    protected function clearRememberCookie(): void
    {
        Cookie::queue(Cookie::forget($this->rememberCookie));
    }
}
