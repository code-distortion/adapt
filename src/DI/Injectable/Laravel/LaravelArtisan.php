<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Injectable class to abstract interaction Laravel Artisan.
 */
class LaravelArtisan
{
    /**
     * Run the given artisan command.
     *
     * @param string               $command      The command to run.
     * @param mixed[]              $parameters   Parameters to pass.
     * @param OutputInterface|null $outputBuffer The buffer to output to.
     * @return integer
     */
    public function call($command, $parameters = [], $outputBuffer = null): int
    {
        // Laravel < 7 would update the config values when calling an artisan command
        // so record the current values before, and replace afterwards
        /** @var Repository $configDTO */
        $configDTO = config();
        $configValues = $configDTO->all();

        $return = Artisan::call($command, $parameters, $outputBuffer);

        config($configValues);

        return $return;
    }

    /**
     * Check if a particular command exists.
     *
     * @param string $command The command to check.
     * @return boolean
     */
    public function commandExists($command): bool
    {
        return array_key_exists($command, Artisan::all());
    }
}
