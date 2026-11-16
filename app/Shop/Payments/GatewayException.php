<?php

declare(strict_types=1);

namespace App\Shop\Payments;

use RuntimeException;

/** The gateway said no, or could not be reached. The message is safe to log. */
final class GatewayException extends RuntimeException {}
