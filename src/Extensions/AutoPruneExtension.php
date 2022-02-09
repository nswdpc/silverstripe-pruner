<?php

namespace NSWDPC\Pruner;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;

/**
 * Applies AutoPrune field to DataObject records using this extension
 * The AutoPrune value can be used to determine if the record should (or should hot) be
 * automatically removed when it becomes stale
 * If you attach this extension to your DataObject, it's up to you to implement the AutoPrune=1
 * filter
 * @author James
 */
class AutoPruneExtension extends DataExtension
{

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'AutoPrune' => 'Boolean',
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'AutoPrune' => 0,
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $indexes = [
        'AutoPrune' => true,
    ];

    /**
     * Update Fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Pruner',
            CheckboxField::create(
                'AutoPrune',
                _t(
                    Pruner::class . ".AUTOMATICALLY_REMOVE_RECORDS",
                    'Automatically remove records after a period of time'
                )
            )
        );
    }
}
