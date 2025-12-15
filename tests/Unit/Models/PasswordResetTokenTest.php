<?php

/**
 * Unit tests for PasswordResetToken model business logic.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Unit\Models;

use Equidna\SwiftAuth\Tests\TestCase;
use Equidna\SwiftAuth\Models\PasswordResetToken;
use ReflectionClass;

/**
 * Tests PasswordResetToken model behavior and validation rules.
 */
final class PasswordResetTokenTest extends TestCase
{
    /**
     * Test that PasswordResetToken table name uses config prefix.
     */
    public function test_table_name_uses_config_prefix(): void
    {
        $token = new PasswordResetToken();
        $expectedPrefix = config('swift-auth.table_prefix', 'swift_');
        $expectedTable = $expectedPrefix . 'PasswordResetTokens';

        $this->assertSame($expectedTable, $token->getTable());
    }

    /**
     * Test that email is the primary key.
     */
    public function test_email_is_primary_key(): void
    {
        $token = new PasswordResetToken();
        $this->assertSame('email', $token->getKeyName());
    }

    /**
     * Test that primary key is not auto-incrementing.
     */
    public function test_primary_key_is_not_auto_incrementing(): void
    {
        $token = new PasswordResetToken();
        $this->assertFalse($token->getIncrementing());
    }

    /**
     * Test that primary key type is string.
     */
    public function test_primary_key_type_is_string(): void
    {
        $token = new PasswordResetToken();
        $this->assertSame('string', $token->getKeyType());
    }

    /**
     * Test that PasswordResetToken does not have timestamps.
     */
    public function test_does_not_have_timestamps(): void
    {
        $token = new PasswordResetToken();
        $this->assertFalse($token->timestamps);
    }

    /**
     * Test that fillable includes email and token.
     */
    public function test_fillable_includes_core_fields(): void
    {
        $token = new PasswordResetToken();
        $reflection = new ReflectionClass($token);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue($token);

        $this->assertContains('email', $fillable);
        $this->assertContains('token', $fillable);
    }

    /**
     * Test that email can be mass assigned.
     */
    public function test_email_is_fillable(): void
    {
        $token = new PasswordResetToken(['email' => 'test@example.com']);
        $this->assertSame('test@example.com', $token->email);
    }

    /**
     * Test that token can be mass assigned.
     */
    public function test_token_is_fillable(): void
    {
        $token = new PasswordResetToken(['token' => 'abc123']);
        $this->assertSame('abc123', $token->token);
    }

    /**
     * Test that email accepts valid email addresses.
     */
    public function test_email_accepts_valid_formats(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk',
            'user_123@sub.example.org',
        ];

        foreach ($validEmails as $email) {
            $token = new PasswordResetToken(['email' => $email]);
            $this->assertSame($email, $token->email);
        }
    }

    /**
     * Test that token accepts alphanumeric strings.
     */
    public function test_token_accepts_alphanumeric(): void
    {
        $token = new PasswordResetToken(['token' => 'abc123XYZ']);
        $this->assertSame('abc123XYZ', $token->token);
    }

    /**
     * Test that token accepts long strings.
     */
    public function test_token_accepts_long_strings(): void
    {
        $longToken = str_repeat('a', 255);
        $token = new PasswordResetToken(['token' => $longToken]);
        $this->assertSame($longToken, $token->token);
    }

    /**
     * Test that created_at is CURRENT_TIMESTAMP.
     */
    public function test_created_at_exists_in_attributes(): void
    {
        $token = new PasswordResetToken(['email' => 'test@example.com', 'token' => 'abc']);
        $this->assertArrayHasKey('created_at', $token->getAttributes());
    }

    /**
     * Test that email is case-sensitive (stored as-is).
     */
    public function test_email_preserves_case(): void
    {
        $email = 'Test@Example.COM';
        $token = new PasswordResetToken(['email' => $email]);
        $this->assertSame($email, $token->email);
    }

    /**
     * Test that token preserves exact value.
     */
    public function test_token_preserves_exact_value(): void
    {
        $token = new PasswordResetToken(['token' => 'aBc123-_=']);
        $this->assertSame('aBc123-_=', $token->token);
    }

    /**
     * Test that model can be instantiated without errors.
     */
    public function test_model_can_be_instantiated(): void
    {
        $token = new PasswordResetToken();
        $this->assertInstanceOf(PasswordResetToken::class, $token);
    }

    /**
     * Test that fillable does not include created_at.
     */
    public function test_created_at_is_not_fillable(): void
    {
        $token = new PasswordResetToken();
        $reflection = new ReflectionClass($token);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue($token);

        $this->assertNotContains('created_at', $fillable);
    }
}
