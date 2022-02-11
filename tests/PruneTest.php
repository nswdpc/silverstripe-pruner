<?php

namespace NSWDPC\Pruner\Tests;

use NSWDPC\Pruner\Pruner;
use NSWDPC\Pruner\InvalidModelListException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

/**
 * Basic pruning test of a record with no files
 * @author James
 */
class PruneTest extends SapphireTest
{

    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var string
     */
    protected static $fixture_file = 'PruneTest.yml';

    /**
     * @var int
     */
    protected $days_ago = 30;

    /**
     * @var int
     */
    protected $limit = 500;

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TestRecord::class,
        TestOtherRecord::class
    ];

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() : void {
        parent::tearDown();
    }

    /**
     * Test DataList / dataClass mismatch
     */
    public function testDataClassMatch() {

        $target_models = [
            TestOtherRecord::class
        ];

        try {
            $pruner = Pruner::create();
            $results = $pruner->prune($this->days_ago, $this->limit, $target_models);
            $this->assertFalse(true, "Prune should have thrown an exception");
        } catch (InvalidModelListException $e) {
            // error caught here
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testAncientPrune() {
        $ancient = $this->objFromFixture(TestRecord::class, 'ancient');

        $list = Injector::inst()->create(TestRecord::class)
                    ->pruneList($this->days_ago, $this->limit);

        $this->assertEquals(1, $list->filter(['ID' => $ancient->ID])->count(), "Ancient is a list record");
    }

    public function testFuturePrune() {
        $future = $this->objFromFixture(TestRecord::class, 'future');

        $list = Injector::inst()->create(TestRecord::class)
                    ->pruneList($this->days_ago, $this->limit);

        $this->assertEquals(0, $list->filter(['ID' => $future->ID])->count(), "Future is not a list record");
    }

    public function testPrune()
    {

        $target_models = [
            TestRecord::class
        ];

        $pruner = Pruner::create();

        $totalRecords = TestRecord::get();
        $totalRecordsCount = $totalRecords->count();

        $expectedToKeep = TestRecord::get()->filter(['ExpectedToBeDeleted' => 0]);
        $expectedToKeepCount = $expectedToKeep->count();

        $expectedToRemove = TestRecord::get()->filter(['ExpectedToBeDeleted' => 1]);
        $expectedToRemoveCount = $expectedToRemove->count();

        $results = $pruner->prune($this->days_ago, $this->limit, $target_models);

        $this->assertTrue(is_array($results) && isset($results['total']) && isset($results['pruned']), "Result is sane");

        // get not pruned
        $unpruned = $totalRecordsCount - $results['pruned'];

        // check record count removed
        $this->assertEquals($expectedToRemoveCount, $results['pruned'], "Pruned == expectedToRemove count");
        // check records remaining
        $this->assertEquals($expectedToKeepCount, $unpruned, "Unpruned == expectedToKeep count");

        $this->assertEmpty($results['keys'], 'Keys in results are empty');
        $this->assertFalse($results['report_only'], 'Was not report_only');


    }

}
