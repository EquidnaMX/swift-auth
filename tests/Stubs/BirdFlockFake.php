<?php

namespace Equidna\BirdFlock;

/**
 * Fake BirdFlock implementation for testing
 */
class BirdFlockFake
{
    protected array $dispatched = [];

    public function dispatch($plan): void
    {
        $this->dispatched[] = $plan;
    }

    public function assertDispatched(callable $callback): void
    {
        foreach ($this->dispatched as $plan) {
            if ($callback($plan)) {
                return;
            }
        }

        throw new \PHPUnit\Framework\AssertionFailedError(
            'Failed asserting that an email was dispatched matching the callback.'
        );
    }

    public function assertNothingDispatched(): void
    {
        if (count($this->dispatched) > 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf('Expected no emails to be dispatched, but %d were dispatched.', count($this->dispatched))
            );
        }
    }
}
