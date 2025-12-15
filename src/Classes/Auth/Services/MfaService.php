<?php

namespace Equidna\SwiftAuth\Classes\Auth\Services;

use Equidna\SwiftAuth\Classes\Auth\Events\MfaChallengeStarted;
use Equidna\SwiftAuth\Models\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Session\Store as Session;

/**
 * Manages Multi-Factor Authentication challenges and state.
 */
class MfaService
{
    protected string $pendingMfaUserKey = 'swift_auth_pending_mfa_user_id';
    protected string $pendingMfaDriverKey = 'swift_auth_pending_mfa_driver';

    public function __construct(
        protected Session $session,
        protected Dispatcher $events,
    ) {
    }

    /**
     * Records a pending MFA challenge without completing login.
     */
    public function startChallenge(
        User $user,
        string $driver = 'otp',
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $sessionId = null,
        array $driverMetadata = []
    ): void {
        $this->session->put($this->pendingMfaUserKey, $user->getKey());
        $this->session->put($this->pendingMfaDriverKey, $driver);

        $this->events->dispatch(new MfaChallengeStarted(
            $user->getKey(),
            $sessionId ?? $this->session->getId(),
            $ipAddress,
            $driverMetadata
        ));

        logger()->info('swift-auth.mfa.challenge_started', [
            'user_id' => $user->getKey(),
            'driver' => $driver,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function clearPendingChallenge(): void
    {
        $this->session->forget($this->pendingMfaUserKey);
        $this->session->forget($this->pendingMfaDriverKey);
    }
}
