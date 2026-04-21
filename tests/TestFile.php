<?php

declare(strict_types=1);

namespace NSWDPC\Pruner\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;

/**
 * A test file
 * @author James
 */
class TestFile extends File implements TestOnly
{
    private static string $table_name = "PrunerTestFile";

    /**
     * Database fields
     */
    private static array $has_one = [
        'Record' => TestRecordWithFile::class,
    ];
}
