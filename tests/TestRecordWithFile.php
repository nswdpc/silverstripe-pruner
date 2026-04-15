<?php

namespace NSWDPC\Pruner\Tests;

use NSWDPC\Pruner\PrunerInterface;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\Dev\TestOnly;

/**
 * A test record with files
 */
class TestRecordWithFile extends DataObject implements TestOnly, PrunerInterface
{
    /**
     * Database fields
     */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'ExpectedToBeDeleted' => 'Boolean'
    ];

    private static array $has_many = [
        'Files' => TestFile::class
    ];

    private static array $cascade_deletes = [
        'Files'
    ];

    /**
     * Defines the database table name
     */
    private static string $table_name = 'PruneTest_TestRecordWithFile';

    public function pruneList(int $days_ago, int $limit): SS_List
    {
        return self::get()->filter(['ExpectedToBeDeleted' => 1]);
    }

    public function onBeforePrune(): void
    {
    }

    public function onAfterPrune(): void
    {
    }

    public function pruneFilesList(): SS_List
    {
        // @phpstan-ignore method.notFound
        return $this->Files();
    }
}
