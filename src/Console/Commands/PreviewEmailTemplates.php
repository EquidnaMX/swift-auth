<?php

/**
 * Artisan command to preview package email templates.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Console\Commands;
use Illuminate\Console\Command;

final class PreviewEmailTemplates extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'swift-auth:preview-email {template?} {--email=} {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render and preview SwiftAuth email templates in the console.';


    public function handle(): int
    {
        $available = [
            'password_reset',
            'password_reset_text',
            'verification',
            'verification_text',
            'account_lockout',
            'account_lockout_text',
        ];

        $rawTemplate = $this->argument('template');
        // Argument could be array/null; normalize to string.
        if (is_array($rawTemplate)) {
            $rawTemplate = $rawTemplate[0] ?? null;
        }
        $template = is_string($rawTemplate) ? trim($rawTemplate) : '';
        $rawEmail = $this->option('email');
        $email = is_string($rawEmail) && $rawEmail !== '' ? $rawEmail : 'user@example.test';
        $rawUrl = $this->option('url');
        $url = is_string($rawUrl) && $rawUrl !== '' ? $rawUrl : 'https://example.test/action';

        if (!$template) {
            $this->info('Available templates:');
            foreach ($available as $t) {
                $this->line(' - ' . $t);
            }

            return 0;
        }

        if (!in_array($template, $available, true)) {
            $this->error('Template not found: ' . ($template === '' ? '[empty]' : $template));
            $this->info('Run without args to list available templates.');

            return 1;
        }

        try {
            $data = [];

            if (str_ends_with($template, '_text') || str_ends_with($template, 'password_reset')) {
                // password reset templates expect resetUrl
                $data['resetUrl'] = $url;
            }

            if (str_contains($template, 'verification')) {
                $data['verifyUrl'] = $url;
            }

            if (str_contains($template, 'verification') || str_contains($template, 'password_reset')) {
                $data['email'] = $email;
            }

            if (str_contains($template, 'account_lockout')) {
                $data['minutes'] = 30;
                $data['email'] = $email;
            }

            $viewName = 'swift-auth::emails.' . $template;

            $output = view($viewName, $data)->render();

            $this->line($output);
        } catch (\RuntimeException $e) {
            $this->error('Failed to render template: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
