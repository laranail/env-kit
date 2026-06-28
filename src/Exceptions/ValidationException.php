<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\Headless\Exceptions;

/** A validation/consistency failure (e.g. schema violation, interpolation cycle). */
final class ValidationException extends EnvKitException {}
