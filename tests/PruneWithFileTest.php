<?php

namespace NSWDPC\Utility\Pruner\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

/**
 * Pruning test of a record with one file
 */
class PruneWithFileTest extends SapphireTest
{
    const TEST_FILE = 'prIk6PdCrgg.jpg';

    protected $test_file_hash = "";
    protected $copied_files = [];
    protected $test_asset_directory = "";
    private $test_asset_folder;

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TestWithFile::class,
        TestFile::class,
    ];

    public function setUp()
    {
        parent::setUp();
        // do this only once
        $source = dirname(__FILE__) . '/files/' . self::TEST_FILE;
        $this->test_file_hash = hash('sha256', file_get_contents($source));
        // create an assets dir to store files in
        $this->test_asset_directory = 'PruneWithFileTest';
    }

    public function tearDown()
    {
        $this->unlinkCopiedFiles();
        parent::tearDown();
    }

    private function unlinkCopiedFiles()
    {
        if ($this->test_asset_folder instanceof Folder) {
            $this->test_asset_folder->delete();
        }
    }

    private function checkFileHash($data)
    {
        $target_hash = hash('sha256', $data);
        return $target_hash == $this->test_file_hash;
    }

    /**
    * @returns File
    */
    private function createFile($prefix, $record_id)
    {
        $folder = Folder::find_or_make($this->test_asset_directory);
        $this->assertTrue($folder instanceof Folder && !empty($folder->ID));

        $this->test_asset_folder = $folder;

        $filename = $prefix . "_" . self::TEST_FILE;
        $file_filename = $this->test_asset_directory . "/" . $filename;

        $source = dirname(__FILE__) . '/files/' . self::TEST_FILE;
        $this->assertTrue(is_readable($source), "Source {$source} is not readable");

        $file = TestFile::create();
        $file->ParentID = $folder->ID;
        $file->TestRecordID = $record_id;// linked to this record
        $file->Name = $file->Title = $filename;
        $file_id = $file->write();

        $file->setFromString(file_get_contents($source), $file_filename);
        $file->write();

        $this->assertEquals($file_id, $file->ID);
        return $file;
    }

    /**
    * Test that a record with a file can be pruned
    */
    public function testPruneWithFile()
    {
        $model = TestWithFile::class;
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

        $this->copied_files = [];
        for ($i=0; $i<$total_test_records; $i++) {
            if ($i % 2 == 0) {
                $datetime_formatted = $discard_dt->format('Y-m-d H:i:s');
                $discard++;
            } else {
                $datetime_formatted = $keep_dt->format('Y-m-d H:i:s');
                $keep++;
            }

            $data = [
                'Title' => "TestWithFile {$i}",
                'DateCheck' => $datetime_formatted
            ];

            //print_r($data);

            $record = new TestWithFile($data);
            $id = $record->write();
            if (!$id) {
                throw new Exception("Failed to write TestWithFile record");
            }

            $file = $this->createFile("f" . $id, $id);
            $this->assertTrue($file instanceof File, "Created file is not a File object");
            // store the file - for later checks
            $this->copied_files[] = [
                'RecordID' => $id,
                'FileID' => $file->ID,
                'Name' => $file->Name,
            ];

            Logger::log("Created file:" . $file->getFilename(), Logger::DEBUG);

            $ids[] = $id;
        }


        $records = TestWithFile::get();

        $this->assertTrue($records->count() == $total_test_records);

        $pruner = Pruner::create();
        $results = $pruner->prune($days_ago, $limit, $target_models);

        $this->assertTrue(is_array($results) && isset($results['total']) && isset($results['pruned']), "Result is sane");
        // the amount pruned must match what we expect
        $this->assertTrue($results['pruned'] == $discard, "Pruned == discard");

        $unpruned = $results['total'] - $results['pruned'];

        // check that they have been deleted
        $kept = 0;
        foreach ($ids as $id) {
            $record = TestWithFile::get()->byId($id);
            if (!empty($record->ID)) {
                $kept++;
            }
        }

        // records in the table should equal the records we have kept
        $this->assertTrue($kept == $keep, "Not all records pruned {$kept}/{$keep}");

        $this->assertTrue(!empty($results['keys']), 'Keys in results are empty');
        $this->assertTrue(!empty($results['file_keys']), 'File_Keys in results are empty');
    }
}
