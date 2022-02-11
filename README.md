# Pruner

Removes records from the database attached to a Silverstripe project, using a queued job with configurable options.

It can be applied to any `SilverStripe\ORM\DataObject` record and is useful for records in tables that can continue to grow, like form submissions.

## Install

```shell
composer require nswdpc/silverstripe-pruner
```

## Userforms support

See `nswdpc/silverstripe-pruner-userforms` module for silverstripe/userforms support.

## Requirements

See composer.json

## Configuration

Configure a project .yml file like so:

```yml
---
Name: prunerconfiglocal
After:
  - '#nswdpc-pruner'
---
NSWDPC\Pruner\Pruner:
  # sample target_models
  target_models:
    # remove/backup submitted forms from the userforms module
    - 'SilverStripe\UserForms\Model\Submission\SubmittedForm'
    # a namespaced DataObject
    - 'Some\Namespaced\DataObject'
```

## Interface

When adding a `SilverStripe\ORM\DataObject` to the list of `target_models`, these DataObjects must:

+ implement the interface `NSWDPC\Utility\Pruner\Interfaces\PrunerInterface` OR
+ have an extension that implements that Interface

The module will ignore models passed to it that do not implement this interface.

See the `PrunerInterface` for documentation.

## Pruning records via the queued job

The `PruneJob` exists to remove the relevant records from the target models.

You can pass in the following constructor arguments:

+ `$days_ago` - the minimum number of days in the past records should be truncated. Older records are removed first. Default  = 30
+ `$limit` - the maximum number of records to remove at one time. Default = 50 (in the case of SubmittedForm records this is a limit per parent class)
+ `$targets` - a comma separated model of DataObject classnames (namespace). If left empty the configured names will be used, if any. This allow you to schedule removal of a certain class of records at a certain time.
+ `$report_only` - 0|1 - pass in 1 to only report on what would be removed.

## Operations pre/post prune

In addition to the usual onBeforeDelete/onAfterDelete Silverstripe methods, the module calls onBeforePrune and onAfterPrune before/after record deletion, respectively.

The order of operation for each record removal is:

1. onBeforePrune
1. delete action:
    1. onBeforeDelete
    1. delete
    1. onAfterDelete
1. onAfterPrune

You must implement these methods, even if you are not carrying out any actions pre/post record pruning.

## Reporting task

A task exists to provide quick report showing what would be removed based on the arguments provided:

Report for the targeted models, older than 15 days, limit 50 records removed per model
```shell
./vendor/bin/sake dev/tasks/ReportOnlyPrunerTask age=15 limit=50 targets=SilverStripe\\UserForms\\Model\\Submission\\SubmittedForm
```

Multiple targets can be separated by a comma. If not targets are provided, the configured value of `NSWDPC\Pruner\Pruner.target_models` is used.

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)

## License

[BSD-3-Clause](./LICENSE.md)

## Security

If you have found a security issue with this module, please email digital[@]dpc.nsw.gov.au in the first instance, detailing your findings.

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
