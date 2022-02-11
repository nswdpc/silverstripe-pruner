<?php

namespace NSWDPC\Pruner;

use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;

/**
 * QueuedJob to process pruning based on input.
 * See constructor doco for options
 * @author James
 */
class PruneJob extends AbstractQueuedJob
{

    use Configurable;

    /**
     * @var int|float
     */
    private static $repeat_hours = 1;// hours

    /**
     * @var int|float
     */
    private static $default_days_ago = 30;

    /**
     * @var int
     */
    private static $default_limit = 50;// 1-hourly job runs should result in 1200 records per target_model per day

    /**
     * @param float $days_ago default days in past to prune up to
     * @param int $limit default record limit (per model)
     * @param string $targets comma separated list of classnames being models to prune records from, if none provided the configured target_models are used
     * @param boolean $report_only when true, results returned show what would have been done
     *
     */
    public function __construct(float $days_ago = 30, int $limit = 50, $targets = "", $report_only = false)
    {

        if (!$days_ago || $days_ago <= 0) {
            $this->days_ago = self::config()->get('default_days_ago');
        } else {
            $this->days_ago = $days_ago;
        }

        if (!$limit || $limit <= 0) {
            $this->limit = self::config()->get('default_limit');
        } else {
            $this->limit = $limit;
        }

        if ($targets == "") {
            $target_models = Config::inst()->get(Pruner::class, 'target_models');
            if (!empty($target_models) && is_array($target_models)) {
                $targets = implode(",", $target_models);
            }
        }

        $this->targets = $targets;
        $this->report_only = (bool)$report_only;
    }

    /**
     * Job title
     * @return string
     */
    public function getTitle()
    {
        $report = $this->report_only ? "(report only)" : "";
        return "Prune Job {$report} > {$this->days_ago} days, {$this->limit} records max. Targets:{$this->targets}";
    }

    /**
     * Process the job
     */
    public function process()
    {
        $targets = explode(",", $this->targets);
        $pruner = Pruner::create();
        if (!$results = $pruner->prune($this->days_ago, $this->limit, $targets, $this->report_only)) {
            $this->addMessage("No valid results - check logs");
        } elseif ($this->report_only) {
            $keys_count = count($results['keys']);
            $file_keys_count = count($results['file_keys']);
            $this->addMessage("REPORT ONLY: would prune {$results['pruned']}/{$results['total']} records. Keys={$keys_count}. Files={$file_keys_count}");
        } else {
            $this->addMessage("Pruned {$results['pruned']}/{$results['total']} records");
        }
        $this->isComplete = true;
        return;
    }

    /**
     * Get the next job start DateTime, formatted
     * If there is no repeat_hours value configure the job does not automatically repeat
     * @return string
     */
    public function getNextStartDateTime() : string
    {
        $hours = self::config()->get('repeat_hours');
        if(!$hours || $hours <= 0) {
            return '';
        } else {
            $dt = new \DateTime();
            $dt->modify('+' . $hours . ' hours');
            return $dt->format('Y-m-d H:i:s');
        }
    }

    /**
     * Recreate job for next run
     */
    public function afterComplete()
    {
        if($nextStartDateTime = $this->getNextStartDateTime()) {
            $job = new PruneJob($this->days_ago, $this->limit, $this->targets, $this->report_only);
            singleton(QueuedJobService::class)->queueJob($job, $nextStartDateTime);
        }
    }
}
