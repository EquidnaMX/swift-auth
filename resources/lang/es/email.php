<?php

/**
 * Spanish email translations.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Lang
 */

return [
    // Email Verification
    'verification_subject' => 'Verifica tu dirección de correo electrónico',
    'verification_title' => '¡Hemos recibido tu solicitud para verificar tu correo!',
    'verification_message' => 'Haz clic en el botón de abajo para continuar con el proceso.',
    'verification_button' => 'Verificar correo',
    'verification_footer' => 'Si no solicitaste esta verificación, puedes ignorar este correo.',
    'verification_expires' => 'Este enlace de verificación expirará en 24 horas.',
    'verification_sent' => 'Correo de verificación enviado exitosamente.',
    'verification_success' => 'Correo verificado exitosamente.',
    'verification_failed' => 'No se pudo enviar el correo de verificación. Por favor intenta más tarde.',
    'verification_invalid' => 'Enlace de verificación inválido.',
    'verification_expired' => 'El token de verificación ha expirado.',
    'verification_already_verified' => 'El correo ya fue verificado.',
    'verification_invalid_email' => 'Dirección de correo inválida.',
    'verification_user_not_found' => 'Usuario no encontrado.',
    'verification_too_many_requests' => 'Demasiadas solicitudes de verificación. Por favor intenta nuevamente en :seconds segundos.',
    'verification_rate_limit' => 'Se enviaron demasiados correos de verificación. Por favor intenta nuevamente en :seconds segundos.',

    // Password Reset
    'reset_subject' => 'Solicitud de restablecimiento de contraseña',
    'reset_title' => '¡Hemos recibido tu solicitud para cambiar la contraseña!',
    'reset_message' => 'Haz clic en el botón de abajo para continuar con el proceso.',
    'reset_button' => 'Restablecer contraseña',
    'reset_footer' => 'Si tú no solicitaste este cambio, puedes ignorar este correo y tu contraseña no cambiará.',
    'reset_expires' => 'Este enlace de restablecimiento de contraseña expirará en 1 hora.',

    // Account Lockout
    'lockout_subject' => 'Cuenta bloqueada por actividad sospechosa',
    'lockout_title' => 'Tu cuenta ha sido bloqueada temporalmente',
    'lockout_message' => 'Detectamos múltiples intentos fallidos de inicio de sesión en tu cuenta. Por tu seguridad, tu cuenta ha sido bloqueada temporalmente.',
    'lockout_duration' => 'Tu cuenta se desbloqueará automáticamente en :minutes minutos.',
    'lockout_contact' => 'Si no intentaste iniciar sesión, por favor contacta al soporte inmediatamente.',
    'lockout_footer' => 'Esta es una medida de seguridad automática para proteger tu cuenta.',
];
