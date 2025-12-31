<?php

/**
 * Handles locale switching for the application.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Controllers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * Manages application locale switching.
 */
final class LocaleController
{
    /**
     * Sets the application locale and redirects back.
     *
     * @param Request          $request HTTP request with locale parameter.
     * @param non-empty-string $locale  Target locale code (e.g., 'en', 'es').
     * @return RedirectResponse Redirect to the previous page.
     */
    public function switch(
        Request $request,
        string $locale,
    ): RedirectResponse {
        $supportedLocales = ['en', 'es'];

        if (!in_array($locale, $supportedLocales, true)) {
            return redirect()->back();
        }

        Session::put(
            key: 'locale',
            value: $locale,
        );

        App::setLocale($locale);

        return redirect()->back();
    }
}
