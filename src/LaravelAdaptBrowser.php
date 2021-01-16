<?php

namespace CodeDistortion\Adapt;

use CodeDistortion\Adapt\Support\Settings;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use Laravel\Dusk\Browser;

class LaravelAdaptBrowser
{
    /**
     * Stop instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Make sure the Browsers pass the database details on to the server when they make requests.
     *
     * @param Browser[] $browsers The browsers to add the connections to.
     * @return void
     */
    public static function useTestDatabases(Browser ...$browsers)
    {
        foreach ($browsers as $browser) {
            // make a small request first, so that cookies can then be set (the browser will reject new cookies before it's loaded a webpage).
            $browser->visit(Settings::INITIAL_BROWSER_REQUEST_PATH);
            static::setBrowserCookie($browser, Settings::CONNECTIONS_COOKIE, serialize(config('database')));
        }
    }

    /**
     * Add the given cookie - account for Laravel not adding the safety-check prefix.
     *
     * @param  Browser  $browser
     * @param  string  $name
     * @param  string  $value
     * @param  int|\DateTimeInterface|null  $expiry
     * @param  array  $options
     * @param  bool  $encrypt
     * @return void
     */
    private static function setBrowserCookie(Browser $browser, string $name, string $value, $expiry = null, array $options = [], $encrypt = true)
    {
        $browser->addCookie($name, $value, $expiry, $options, $encrypt);

        if (!$encrypt) {
            return;
        }

        // check if Laravel forgot to add the safety-check prefix to the value
        $plainValue = $browser->plainCookie($name);
        $decryptedValue = decrypt(rawurldecode($plainValue), $unserialize = false);
        $prefix = CookieValuePrefix::create($name, Crypt::getKey());
        $hasValuePrefix = strpos($decryptedValue, $prefix) === 0;
        if (!$hasValuePrefix) {
            $browser->addCookie($name, $prefix.$value, $expiry, $options, $encrypt);
        }
    }
}
