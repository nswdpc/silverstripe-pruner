<?php

namespace NSWDPC\Pruner;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * The Pruner stores configuration and carries out prune-ing (default by date)
 * @note remove backup code
 * @author James
 */
class Pruner
{
    use Configurable;

    use Injectable;

    private static $target_models = [];// an array of all models that will be pruned

    protected $results = [];

    /**
     * Check if the model provided is a valid model for our purposes, returns an instance of it if so.
     * @return DataObject
     * @throws InvalidModelException
     */
    private function isValidModel(string $model)
    {
        try {
            if (!class_exists($model)) {
                throw new \Exception("Class: '{$model}' is not loaded or does not exist");
            }

            $instance = Injector::inst()->create($model);
            if (!($instance instanceof DataObject)) {
                throw new \Exception("{$model} is not an instance of DataObject");
            }

            if (!self::recordIsPruneable($instance)) {
                throw new \Exception("Neither {$model} nor its extensions implement PrunerInterface");
            }

            return $instance;
        } catch (\Exception $e) {
            throw new InvalidModelException($e->getMessage());
        }
    }

    /**
     * Gathers a datalist of supporting models and prunes matching records
     * @param float $days_ago number of days in the past to prune up to. E.g 30 will prune matching records up to 30 days ago
     * @param int $limit limit the number of records returned in any one list
     * @param array $targets
     * @param boolean $report_only
     * @return array of results, either complete or partial results (if an error occurred)
     */
    public function prune(float $days_ago = 30, int $limit = 500, array $targets = [], bool $report_only = false) : array
    {
        $this->results = [
            'total' => 0,
            'pruned' => 0,
            'keys' => [],
            'file_keys' => [],
            'report_file_keys' => [],
            'report_only' => $report_only
        ];

        if (empty($targets)) {
            // use configured targets if none passed in
            $targets = $this->config()->get('target_models');
        }

        if (empty($targets) || !is_array($targets)) {
            Logger::log("Pruner::prune called but there are no target_models configured", Logger::NOTICE);
            throw new \InvalidArgumentException("No target models configured");
        }

        if ($days_ago <= 0) {
            $days_ago = 30;
        }

        if ($limit <= 0) {
            $limit = 500;
        }

        foreach ($targets as $model) {
            /**
             * This is a destructive process
             * The model given must implement PrunerInterface
             * OR
             * Have an extension that implements PrunerInterface
             */
            try {

                //attempt to grab a valid instance of the model
                $instance = $this->isValidModel($model);

                $list = $instance->pruneList($days_ago, $limit);

                if (!is_object($list)) {
                    throw new InvalidModelListException("{$model} did not return a valid response");
                }

                if (!($list instanceof ArrayList) && !($list instanceof DataList)) {
                    throw new InvalidModelListException("{$model} did not return an ArrayList || DataList - got a " . get_class($list));
                }

                // restrict DataList dataClass to the class of  model instance
                if($list instanceof DataList) {
                    $dataClass = $list->dataClass();
                    if(!($instance instanceof $dataClass) ) {
                        throw new InvalidModelListException("Returned DataList of type '{$dataClass}' should be an instance of '{$model}'");
                    }
                }

                $list_count = $list->count();
                if ($list_count == 0) {
                    Logger::log("Pruner::prune {$model}::pruneList() has no matching records.. this might be expected.", Logger::INFO);
                } elseif ($report_only) {
                    // only report what would happen
                    $this->results['total'] = $list_count;
                    $this->results['pruned'] = 0;
                    foreach ($list as $record) {
                        $result_key = $record->ID . ":" . $record->ClassName . ":" . $record->Created;
                        Logger::log("Pruner::prune REPORT record {$result_key}", Logger::INFO);
                        $this->results['keys'][] = $result_key;
                        if ($files = $this->getRecordFiles($record)) {
                            foreach ($files as $file) {
                                $file_key = $file->ID . ":" . $file->ClassName;
                                Logger::log("Pruner::prune REPORT linked file {$file_key}", Logger::INFO);
                                $this->results['file_keys'][] = $file_key;
                                $this->results['report_file_keys'][ $result_key ][] = $file->ID . ":" . $file->ClassName . ":" . $file->Created;
                            }
                        }
                    }
                } else {
                    foreach ($list as $record) {
                        try {
                            $this->results['total']++;
                            $this->pruneRecord($record);
                            $this->results['pruned']++;
                        } catch (Exception $e) {
                            // Logger::log("Pruner::prune failed to prune record {$model}/#{$record->ID}. Type:" . get_class($e), Logger::INFO);
                            Logger::log("Pruner::prune message was: {$e->getMessage()}", Logger::NOTICE);
                        }
                    }
                }
            } catch (Exception $e) {
                Logger::log("Pruner::prune failed on line {$e->getLine()} of file {$e->getFile()} message={$e->getMessage()} type=" . get_class($e), Logger::NOTICE);
            }
        }

        return $this->results;
    }

    /**
     * Determine whether the passed instance can be pruned
     * @param DataObject an instance to test whether it implement or an extension implements PrunerInterface
     * @return bool
     */
    public static function recordIsPruneable(DataObject $instance) : bool {
        $implements = self::implementsPrunerInterface($instance);
        if (!$implements) {
            // check whether an extension implements the Interface
            $extensions = $instance->getExtensionInstances();
            foreach ($extensions as $extension_class => $extension_instance) {
                $implements = self::implementsPrunerInterface($extension_instance);
                if ($implements) {
                    // huzzah
                    break;
                }
            }
        }
        return $implements;
    }

    /**
     * Determine whether an instance implements {@link \NSWDPC\Pruner\PrunerInterface}
     */
    protected static function implementsPrunerInterface(object $instance) : bool
    {
        $rc = new \ReflectionClass($instance);
        return $rc->implementsInterface(PrunerInterface::class);
    }

    /**
     * Prune the passed record implementing PrunerInterface
     * @param DataObject implementing PrunerInterface
     * @throws Exception
     * @return bool
     */
    protected function pruneRecord(DataObject $record) : bool
    {
        // The record should delete itself in prune(), if it can, along with all associations
        $record->onBeforePrune();
        $record->delete();
        $record->onAfterPrune();
        return true;
    }

    /**
     * When files are found, each file is returned as JSON encoded string with the following keys:
     *  content: base64_encoded file content
     *  content-type: the content type of the file
     *  name: the name of the file
     * @param DataObject implementing PrunerInterface
     */
    private function getRecordFiles(DataObject $record)
    {
        return $record->pruneFilesList();
    }
}
