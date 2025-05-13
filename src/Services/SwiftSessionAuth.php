<?php

namespace Teleurban\SwiftAuth\Services;

use Teleurban\SwiftAuth\Models\User;
use Illuminate\Session\Store as Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SwiftSessionAuth
{
    protected Session $session;
    protected string $sessionKey = 'swift_auth_user_id';

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function login(User $user): void
    {
        $this->session->put($this->sessionKey, $user->getKey());
    }

    public function logout(): void
    {
        $this->session->forget($this->sessionKey);
    }

    public function check(): bool
    {
        return $this->session->has($this->sessionKey);
    }

    public function id(): ?int
    {
        return $this->session->get($this->sessionKey);
    }

    public function user(): ?User
    {
        $id = $this->id();
        return $id ? User::find($id) : null;
    }

    public function userOrFail(): User
    {
        $id = $this->id();

        if (!$id || !$user = User::find($id)) {
            throw new ModelNotFoundException("User not found");
        }

        return $user;
    }
}
