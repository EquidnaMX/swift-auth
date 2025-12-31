<?php

/**
 * English email translations.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Lang
 */

return [
    // Email Verification
    'verification_subject' => 'Verify Your Email Address',
    'verification_title' => 'We have received your request to verify your email!',
    'verification_message' => 'Click the button below to continue with the process.',
    'verification_button' => 'Verify Email',
    'verification_footer' => 'If you did not request this verification, you can ignore this email.',
    'verification_expires' => 'This verification link will expire in 24 hours.',
    'verification_sent' => 'Verification email sent successfully.',
    'verification_success' => 'Email verified successfully.',
    'verification_failed' => 'Failed to send verification email. Please try again later.',
    'verification_invalid' => 'Invalid verification link.',
    'verification_expired' => 'Verification token has expired.',
    'verification_already_verified' => 'Email already verified.',
    'verification_invalid_email' => 'Invalid email address.',
    'verification_user_not_found' => 'User not found.',
    'verification_too_many_requests' => 'Too many verification requests. Please try again in :seconds seconds.',
    'verification_rate_limit' => 'Too many verification emails sent. Please try again in :seconds seconds.',

    // Password Reset
    'reset_subject' => 'Password Reset Request',
    'reset_title' => 'We have received your request to reset your password!',
    'reset_message' => 'Click the button below to continue with the process.',
    'reset_button' => 'Reset Password',
    'reset_footer' => 'If you did not request this password reset, you can ignore this email and your password will not change.',
    'reset_expires' => 'This password reset link will expire in 1 hour.',

    // Account Lockout
    'lockout_subject' => 'Account Locked Due to Suspicious Activity',
    'lockout_title' => 'Your account has been temporarily locked',
    'lockout_message' => 'We detected multiple failed login attempts on your account. For your security, your account has been temporarily locked.',
    'lockout_duration' => 'Your account will be automatically unlocked in :minutes minutes.',
    'lockout_contact' => 'If you did not attempt to log in, please contact support immediately.',
    'lockout_footer' => 'This is an automated security measure to protect your account.',
];
