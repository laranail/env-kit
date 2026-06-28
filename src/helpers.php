<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;

if (! function_exists('env_kit')) {
    /**
     * Thin, optional sugar over the bound EnvKit root service.
     *
     * - `env_kit()`                  → the bound EnvKitInterface instance (chain anything).
     * - `env_kit('APP_NAME')`        → read the value (or null).
     * - `env_kit('APP_NAME', 'def')` → read the value or the given default.
     *
     * Read-only shortcut: there is intentionally no write form here — use
     * `env_kit()->set(...)`, the `EnvKit` facade, or constructor DI of EnvKitInterface.
     * This helper is sugar over the container-bound instance, so `EnvKit::fake()`
     * transparently fakes it too. Core logic never lives in a procedural helper.
     *
     * @template TDefault
     * @param  string|null  $key
     * @param  TDefault  $default
     * @return ($key is null ? EnvKitInterface : mixed|TDefault)
     */
    function env_kit(?string $key = null, mixed $default = null): mixed
    {
        $envKit = app(EnvKitInterface::class);

        if ($key === null) {
            return $envKit;
        }

        return $envKit->get($key, $default);
    }
}
