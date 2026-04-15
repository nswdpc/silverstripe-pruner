<?php

namespace NSWDPC\Pruner\Tests;

use NSWDPC\Pruner\PrunerInterface;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;

/**
 * A test record with no files
 */
class TestRecord extends DataObject implements TestOnly, PrunerInterface
{

    /**
     * Defines the database table name
     */
    private static string $table_name = 'PruneTest_TestRecord';

    /**
     * Database fields
     */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'ExpectedToBeDeleted' => 'Boolean'
    ];

    public function pruneList(int $days_ago, int $limit) : SS_List
    {
        return self::get()->filter(['ExpectedToBeDeleted' => 1]);
    }

    public function onBeforePrune() : void
    {
    }

    public function onAfterPrune() : void
    {
    }

    public function pruneFilesList() : SS_List
    {
        return ArrayList::create();
    }
}
