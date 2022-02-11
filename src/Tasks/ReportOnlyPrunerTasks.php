<?php

namespace NSWDPC\Pruner;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * Run a task to report on which records would be pruned
 * @author James
 */
class ReportOnlyPrunerTask extends BuildTask
{

    /**
     * @inheritdoc
     */
    protected $title = "Tasks to report on which records would be pruned based on arguments provided";

    /**
     * @inheritdoc
     */
    protected $description = "This tasks does not delete any records";

    /**
     * @inheritdoc
     */
    private static $segment = "ReportOnlyPrunerTask";

    /**
     * Run the task
     */
    public function run($request)
    {
        // options
        $age = intval($request->getVar('age'));
        if(!$age) {
            $age = 30;
        }
        DB::alteration_message("Using age={$age}", "warning");
        $limit = intval($request->getVar('limit'));
        if(!$limit) {
            $limit = 500;
        }
        DB::alteration_message("Using limit={$age}", "warning");

        $targets = $request->getVar('targets');
        $target_models = array_filter( array_map("trim", explode(",", $targets) ) );
        if(empty($target_models)) {
            DB::alteration_message("Target models is empty", "warning");
        }

        $pruner = Pruner::create();
        $results = $pruner->prune($age, $limit, $target_models, true);
        if (!$results) {
            DB::alteration_message("Task seems to have failed", "error");
            return;
        } else {
            $output = "\tREPORT\n";
            $output .= "\t======\n";
            $output .= "\tTotal: {$results['pruned']}/{$results['total']} records pruned\n";
            $output .= "\t======\n";
            $output .= "\tKEYS\n";
            foreach ($results['keys'] as $key) {
                $output .= "\n\tRECORD: {$key}\n";
                if (!empty($results['report_file_keys'][ $key ])) {
                    $output .= "\t\tFILES: " .  count($results['report_file_keys'][ $key ]) . "\n";
                    foreach ($results['report_file_keys'][ $key ] as $file_key) {
                        $output .= "\t\t\t{$file_key}\n";
                    }
                }
            }
            DB::alteration_message($output, "info");
        }
    }
}
