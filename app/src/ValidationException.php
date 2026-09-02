<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/** Raised for problems that are safe to show the person filling in the form. */
final class ValidationException extends RuntimeException
{
}
