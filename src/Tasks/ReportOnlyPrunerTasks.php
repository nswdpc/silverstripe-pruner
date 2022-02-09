<?php

namespace NSWDPC\Pruner;

use NSWDPC\Utility\Pruner\Models\Pruner;
use SilverStripe\Dev\BuildTask;

/**
 * Run a task to report on which records would be pruned
 * @author James
 */
class ReportOnlyPrunerTask extends BuildTask
{
    
    /**
     * @var string
     */
    protected $title = "Tasks to report on which records would be pruned based on arguments provided";

    /**
     * @var string
     */
    protected $description = "This tasks does not delete any records";

    /**
     * Run the task
     */
    public function run($request)
    {
        // options
        $days_ago = (int)$request->getVar('days_ago');
        $targets = $request->getVar('targets');
        $limit = (int)$request->getVar('limit');

        $target_models = [];
        if (strpos($targets, ",") !== false) {
            $target_models = explode(",", $targets);
        } else {
            $target_models = [ $targets ];
        }

        $pruner = Pruner::create();
        $results = $pruner->prune($days_ago, $limit, $target_models, true);
        if (!$results) {
            print "Seems to have failed";
            exit(1);
        } else {
            print "REPORT\n";
            print "======\n";
            print "Total: {$results['pruned']}/{$results['total']} records pruned\n";
            print "======\n";
            print "KEYS\n";
            foreach ($results['keys'] as $key) {
                print "\nRECORD: {$key}\n";
                if (!empty($results['report_file_keys'][ $key ])) {
                    print "\tFILES: " .  count($results['report_file_keys'][ $key ]) . "\n";
                    foreach ($results['report_file_keys'][ $key ] as $file_key) {
                        print "\t\t{$file_key}\n";
                    }
                }
            }
            return true;
        }
    }
}
