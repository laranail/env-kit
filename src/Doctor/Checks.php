<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Doctor;

use Simtabi\Laranail\EnvKit\Headless\Contracts\DoctorRuleInterface;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\BlankValue;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\ByteOrderMark;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\DuplicateKeys;
use Simtabi\Laranail\EnvKit\Headless\Doctor\Rules\MissingTrailingNewline;
use Simtabi\Laranail\EnvKit\Headless\Document\EnvDocument;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;

/**
 * Builds the package-tools {@see DoctorCheck} set that surfaces env-kit's
 * bespoke doctor rules through the standard doctor surface.
 *
 * The built-in rule set is wrapped one-per-check (the same rules
 * {@see Doctor::withDefaults()} runs).
 * Consumer/tagged rules aren't collected until after boot, so they stay the
 * exclusive concern of the bespoke `laranail::env-kit.doctor` command; the
 * standard surface reports env-kit's core hygiene checks.
 */
final class Checks
{
    /**
     * @return list<DoctorCheck>
     */
    public static function all(): array
    {
        // One memoised document read shared across every wrapped rule, resolved
        // lazily at doctor time from the configured .env path.
        $document = null;
        $provider = static function () use (&$document): EnvDocument {
            if ($document instanceof EnvDocument) {
                return $document;
            }

            $path = config('env-kit.path');
            $path = is_string($path) && $path !== '' ? $path : base_path('.env');

            return $document = EnvDocument::parse(is_file($path) ? (string) @file_get_contents($path) : '');
        };

        return array_map(
            static fn (array $spec): BespokeRuleCheck => new BespokeRuleCheck(
                $spec['rule'],
                $provider,
                $spec['name'],
                $spec['description'],
            ),
            self::specs(),
        );
    }

    /**
     * @return list<array{rule: DoctorRuleInterface, name: string, description: string}>
     */
    private static function specs(): array
    {
        return [
            [
                'rule' => new DuplicateKeys,
                'name' => 'env:duplicate-keys',
                'description' => 'No .env key is defined more than once.',
            ],
            [
                'rule' => new BlankValue,
                'name' => 'env:blank-values',
                'description' => 'No .env key is assigned a blank value.',
            ],
            [
                'rule' => new ByteOrderMark,
                'name' => 'env:byte-order-mark',
                'description' => 'The .env file has no leading UTF-8 byte-order mark.',
            ],
            [
                'rule' => new MissingTrailingNewline,
                'name' => 'env:trailing-newline',
                'description' => 'The .env file ends with a trailing newline.',
            ],
        ];
    }
}
