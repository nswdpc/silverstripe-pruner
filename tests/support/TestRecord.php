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
     * @var string
     */
    private static $table_name = 'PruneTest_TestRecord';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'ExpectedToBeDeleted' => 'Boolean'
    ];

    public function pruneList(int $days_ago, int $limit) : SS_List
    {
        $list = self::get()->filter(['ExpectedToBeDeleted' => 1]);
        return $list;
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
