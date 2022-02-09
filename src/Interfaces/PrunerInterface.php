<?php

namespace NSWDPC\Pruner;

/**
 * Interface for supporting classes that allow pruning via {@link NSWDPC\Pruner\Pruner}
 * @author James
 */
interface PrunerInterface
{
    
    /**
     * Return a list of records to be pruned
     * @param int $daysAgo
     * @return DataList
     */
    public function pruneList(int $days_ago, int $limit);
    public function onBeforePrune();
    public function onAfterPrune();
    public function pruneFilesList();
}
