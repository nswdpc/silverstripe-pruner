<?php

namespace NSWDPC\Pruner;

use SilverStripe\ORM\SS_List;

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
    public function pruneList(int $days_ago, int $limit) : SS_List;
    public function onBeforePrune() : void;
    public function onAfterPrune() : void;
    public function pruneFilesList() : SS_List;
}
