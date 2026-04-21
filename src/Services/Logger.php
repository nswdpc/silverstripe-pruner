<?php

declare(strict_types=1);

namespace NSWDPC\Pruner;

use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

/**
 * Simple Logging class
 * @author James
 */
class Logger
{
    public const DEBUG = 'DEBUG';

    public const INFO = 'INFO';

    public const NOTICE = 'NOTICE';

    public const WARNING = 'WARNING';

    public const ERROR = 'ERROR';

    public const CRITICAL = 'CRITICAL';

    public const ALERT = 'ALERT';

    public const EMERGENCY = 'EMERGENCY';

    public static function log(string|\Stringable $message, $level = "DEBUG")
    {
        Injector::inst()->get(LoggerInterface::class)->log($level, $message);
    }
}
