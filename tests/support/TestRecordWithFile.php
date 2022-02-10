<?php

namespace NSWDPC\Pruner\Tests;

use NSWDPC\Pruner\PrunerInterface;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Dev\TestOnly;

/**
 * A test record with files
 */
class TestRecordWithFile extends DataObject implements TestOnly, PrunerInterface
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'ExpectedToBeDeleted' => 'Boolean'
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Files' => TestFile::class
    ];

    /**
     * @var array
     */
    private static $cascade_deletes = [
        'Files'
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'PruneTest_TestRecordWithFile';

    public function pruneList($days_ago, $limit) : SS_List
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
        return $this->Files();
    }
}
