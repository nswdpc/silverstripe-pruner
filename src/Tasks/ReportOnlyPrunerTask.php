<?php

namespace NSWDPC\Pruner;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Run a task to report on which records would be pruned
 * @author James
 */
class ReportOnlyPrunerTask extends BuildTask
{
    /**
     * @inheritdoc
     */
    protected string $title = "Tasks to report on which records would be pruned based on arguments provided";

    /**
     * @inheritdoc
     */
    protected static string $description = "This tasks does not delete any records";

    /**
     * @inheritdoc
     */
    protected static string $commandName = "ReportOnlyPrunerTask";

    /**
     * Run the task
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // options
        $age = floatval($input->getOption('age'));
        if (!$age) {
            $age = 30;
        }

        $output->writeln("Using age={$age}");
        $limit = intval($input->getOption('limit'));
        if (!$limit) {
            $limit = 500;
        }

        $output->writeln("Using limit={$limit}");

        $targets = $input->getOption('targets');
        $target_models = array_filter(array_map(trim(...), explode(",", $targets)));
        if ($target_models === []) {
            $output->writeln("Target models is empty");
        }

        $pruner = Pruner::create();
        $results = $pruner->prune($age, $limit, $target_models, true);
        if ($results['error']) {
            $output->writeln("Task seems to have failed: " . $results['last_error_msg']);
            return Command::FAILURE;
        } else {
            $report = "\tREPORT\n";
            $report .= "\t======\n";
            $report .= "\tTotal: {$results['pruned']}/{$results['total']} records pruned\n";
            $report .= "\t======\n";
            $report .= "\tKEYS\n";
            foreach ($results['keys'] as $key) {
                $report .= "\n\tRECORD: {$key}\n";
                if (!empty($results['report_file_keys'][ $key ])) {
                    $report .= "\t\tFILES: " .  count($results['report_file_keys'][ $key ]) . "\n";
                    foreach ($results['report_file_keys'][ $key ] as $file_key) {
                        $report .= "\t\t\t{$file_key}\n";
                    }
                }
            }

            $output->writeln($report);
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('age', null, InputOption::VALUE_NONE, 'Older than this age'),
            new InputOption('targets', null, InputOption::VALUE_NONE, 'FQCN target class names'),
            new InputOption('limit', null, InputOption::VALUE_NONE, 'Limit of records'),
        ];
    }
}
