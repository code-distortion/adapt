<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\Exceptions;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Throwable;

/**
 * Command to list the Adapt snapshot and test-databases.
 */
abstract class AbstractAdaptCommand extends Command
{
    use CommandFunctionalityTrait;



    /**
     * Execute the console command.
     *
     * @return void
     * @throws Throwable When something goes wrong.
     */
    public function handle()
    {
        try {

            /** @var Application $app */
            $app = app();
            if (!$app->environment('testing')) {
                LaravelSupport::useTestingConfig();
            }

            $this->performHandleWork();

        } catch (Throwable $e) {
            Exceptions::logException($this->newLog(), $e, true);
            throw $e;
        }
    }

    /**
     * Carry out the console command work.
     *
     * @return void
     */
    abstract protected function performHandleWork();
}
