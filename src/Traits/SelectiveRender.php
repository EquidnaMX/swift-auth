<?php

/**
 * Selective render helper for Blade or Inertia frontends.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Traits
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Traits;

use Illuminate\Contracts\View\View;
use Inertia\Response;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;

/**
 * Trait SelectiveRender
 *
 * This trait provides a method to selectively render Blade views or Inertia components
 * based on the configuration of the frontend. It also ensures that flash messages
 * (success, error, status) are passed to the view or component.
 *
 * @package Equidna\SwiftAuth\Traits
 */
trait SelectiveRender
{
    /**
     * Renders a view or Inertia component based on the frontend configuration.
     * Flash messages (success, error, status) are automatically added to the view data.
     *
     * @param string $bladeView The Blade view name to render (if frontend is Blade).
     * @param string $inertiaComponent The Inertia component name to render (if frontend is Inertia).
     * @param array<string,mixed> $data Additional data to pass to the view or component.
     *
     * @return \Illuminate\Contracts\View\View|Response The rendered Blade view or Inertia component.
     */
    protected function render(string $bladeView, string $inertiaComponent, array $data = []): View|Response
    {
        $flashMessages = [
            'success' => session('success'),
            'error' => session('error'),
            'status' => session('status'),
        ];

        $data = array_merge($data, $flashMessages);

        return Config::get('swift-auth.frontend') === 'blade'
            ? view($bladeView, $data)
            : Inertia::render($inertiaComponent, $data);
    }
}
