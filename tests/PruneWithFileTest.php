<?php

namespace NSWDPC\Pruner\Tests;

use NSWDPC\Pruner\Pruner;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use Silverstripe\Assets\Dev\TestAssetStore;

/**
 * Pruning test of a record with one file
 */
class PruneWithFileTest extends SapphireTest
{

    /**
     * @var string
     */
    protected static $fixture_file = 'PruneWithFileTest.yml';

    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TestRecordWithFile::class,
        TestFile::class
    ];

    /**
     * @var int
     */
    protected $days_ago = 30;

    /**
     * @var int
     */
    protected $limit = 500;

    public function setUp()
    {
        parent::setUp();

        TestAssetStore::activate('PruneWithFileTest');
        $fileIDs = $this->allFixtureIDs(TestFile::class);
        foreach ($fileIDs as $fileID) {
            /** @var File $file */
            $file = DataObject::get_by_id(TestFile::class, $fileID);
            $file->setFromString(str_repeat('x', 1000000), $file->getFilename());
        }

    }

    public function tearDown()
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testAncientPrune() {
        $ancient = $this->objFromFixture(TestRecordWithFile::class, 'ancient');
        $fileCount = $ancient->Files()->count();

        $sng = Injector::inst()->create(TestRecordWithFile::class);

        $pruneList = $sng->pruneList($this->days_ago, $this->limit);

        $filteredList = $pruneList->filter(['ID' => $ancient->ID]);

        $this->assertEquals(1, $filteredList->count(), "Ancient is a list record");

        $pruneFileList = $filteredList->first()->pruneFilesList();

        $this->assertEquals($fileCount, $pruneFileList->count(), "File count matches");
    }

    public function testFuturePrune() {
        $future = $this->objFromFixture(TestRecordWithFile::class, 'future');
        $fileCount = $future->Files()->count();

        $sng = Injector::inst()->create(TestRecordWithFile::class);

        $pruneList = $sng->pruneList($this->days_ago, $this->limit);

        $filteredList = $pruneList->filter(['ID' => $future->ID]);

        $this->assertEquals(0, $filteredList->count(), "Future is not a list record");
    }

    public function testPrune()
    {

        $target_models = [
            TestRecordWithFile::class
        ];

        $pruner = Pruner::create();

        $totalRecords = TestRecordWithFile::get();
        $totalRecordsCount = $totalRecords->count();

        $expectedToKeep = TestRecordWithFile::get()->filter(['ExpectedToBeDeleted' => 0]);
        // store files IDs expected to be kept
        $expectedToKeepFiles = [];
        foreach($expectedToKeep as $testRecord) {
            $expectedToKeepFiles = array_merge($expectedToKeepFiles, $testRecord->Files()->column('ID'));
        }
        $expectedToKeepCount = $expectedToKeep->count();

        $expectedToRemove = TestRecordWithFile::get()->filter(['ExpectedToBeDeleted' => 1]);
        // store files IDs expected to be removed
        $expectedToRemoveFiles = [];
        foreach($expectedToRemove as $testRecord) {
            $expectedToRemoveFiles = array_merge($expectedToRemoveFiles, $testRecord->Files()->column('ID'));
        }
        $expectedToRemoveCount = $expectedToRemove->count();

        $results = $pruner->prune($this->days_ago, $this->limit, $target_models);

        $this->assertTrue(is_array($results) && isset($results['total']) && isset($results['pruned']), "Result is sane");

        // get not pruned
        $unpruned = $totalRecordsCount - $results['pruned'];

        // check record count removed
        $this->assertEquals($expectedToRemoveCount, $results['pruned'], "Pruned == expectedToRemove count");
        // check records remaining
        $this->assertEquals($expectedToKeepCount, $unpruned, "Unpruned == expectedToKeep count");

        // check files remove
        $filesRemoved = File::get()->filter(['ID' => $expectedToRemoveFiles]);
        $this->assertEquals( 0, $filesRemoved->count(), "All expected files removed");

        // check files kept
        $filesKept = File::get()->filter(['ID' => $expectedToKeepFiles]);
        $this->assertEquals( count($expectedToKeepFiles), $filesKept->count(), "All kepts files retained");

        $this->assertEmpty($results['keys'], 'Keys in results are empty');
        $this->assertFalse($results['report_only'], 'Was not report_only');


    }
}
