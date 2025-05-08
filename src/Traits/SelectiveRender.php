<?php

namespace Teleurban\SwiftAuth\Traits;

use Inertia\Inertia;

trait SelectiveRender
{
    protected function render($bladeView, $inertiaComponent, $data = [])
    {
        // Pasar mensajes de flash a la vista
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
