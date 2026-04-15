<?php

declare(strict_types=1);

namespace NSWDPC\Pruner;

use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Security\Security;

/**
 * Simple Logging class
 * @author James
 */
class Logger
{
    const DEBUG = 'DEBUG';

    const INFO = 'INFO';

    const NOTICE = 'NOTICE';

    const WARNING = 'WARNING';

    const ERROR = 'ERROR';

    const CRITICAL = 'CRITICAL';

    const ALERT = 'ALERT';

    const EMERGENCY = 'EMERGENCY';

    public static function log(string|\Stringable $message, $level = "DEBUG")
    {
        Injector::inst()->get(LoggerInterface::class)->log($level, $message);
    }
}
