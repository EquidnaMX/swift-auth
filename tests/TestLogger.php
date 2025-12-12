<?php

namespace Tests;

use Psr\Log\AbstractLogger;

class TestLogger extends AbstractLogger
{
    public array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public function hasLogged(string $level, ?callable $callback = null): bool
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === $level) {
                if ($callback === null || $callback($log['message'], $log['context'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
