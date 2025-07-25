<?php

namespace Teleurban\SwiftAuth\Mail;

use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class PasswordResetMail  extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public string $email;

    public function __construct(string $email, string $token)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        $resetUrl = url("/swift-auth/password/{$this->token}?email=" . urlencode($this->email));

        return $this->subject('Recuperación de contraseña')
            ->html("
                <p>Hola,</p>
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p><a href='{$resetUrl}'>Haz clic aquí para restablecerla</a></p>
                <p>Si no hiciste esta solicitud, ignora este correo.</p>
            ");
    }
}
