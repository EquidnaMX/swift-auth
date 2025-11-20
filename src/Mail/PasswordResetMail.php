<?php

/**
 * Mailable used to send password reset instructions.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Mail
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Mail;

use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $token;
    public string $email;

    public function __construct(string $email, string $token)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Build the password reset email.
     *
     * @return $this
     */
    public function build()
    {
        $routePrefix = config('swift-auth.route_prefix', 'swift-auth');
        $resetUrl = url("/{$routePrefix}/password/{$this->token}?email=" . urlencode($this->email));

        return $this->subject('Restablecer contraseña')
            ->view('swift-auth::emails.password_reset', [
                'resetUrl' => $resetUrl,
                'email' => $this->email,
                'token' => $this->token,
            ]);
    }
}
