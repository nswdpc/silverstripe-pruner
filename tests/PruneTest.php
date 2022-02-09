<?php

namespace NSWDPC\Pruner\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Config\Config;

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
     * @var array
     */
    protected static $extra_dataobjects = [
        TestRecord::class,
    ];

    public function testPrune()
    {
        $model = TestRecord::class;
        $target_models = [
            $model
        ];

        $pruner = Pruner::create();

        $days_ago = 30;
        $limit = 500;

        $keep = $discard = 0;
        $ids = [];

        $total_test_records = 10;

        $dt = new \DateTime();
        $dt->modify("-{$days_ago} days");// put it on the boundary
        $discard_dt = clone($dt);
        $discard_dt->modify("-5 days");// 35 days ago will discard
        $keep_dt = clone($dt);
        $keep_dt->modify("+5 days");// 25 days will keep

        for ($i=0; $i<$total_test_records; $i++) {
            if ($i % 2 == 0) {
                $datetime_formatted = $discard_dt->format('Y-m-d H:i:s');
                $discard++;
            } else {
                $datetime_formatted = $keep_dt->format('Y-m-d H:i:s');
                $keep++;
            }
            
            $data = [
                'Title' => "TestRecord {$i}",
                'DateCheck' => $datetime_formatted
            ];
            $record = new TestRecord($data);
            $id = $record->write();
            if (!$id) {
                throw new Exception("Failed to write TestRecord record");
            }

            $ids[] = $id;
        }

        $records = TestRecord::get();

        $this->assertTrue($records->count() == $total_test_records);

        $results = $pruner->prune($days_ago, $limit, $target_models);

        $this->assertTrue(is_array($results) && isset($results['total']) && isset($results['pruned']), "Result is sane");
        // the amount pruned must match what we expect
        $this->assertTrue($results['pruned'] == $discard, "Pruned == discard");

        $unpruned = $results['total'] - $results['pruned'];

        // check that they have been deleted
        $kept = 0;
        foreach ($ids as $id) {
            $record = TestRecord::get()->byId($id);
            if (!empty($record->ID)) {
                $kept++;
            }
        }

        // records in the table should equal the records we have kept
        $this->assertTrue($kept == $keep, "Not all records pruned {$kept}/{$keep}");
        $this->assertTrue(!empty($results['keys']), 'Keys in results are empty');
    }
}
