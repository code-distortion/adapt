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
    public function call(string $command, array $parameters = [], ?OutputInterface $outputBuffer = null): int
    {
        // Laravel < 7 would update the config values
        // record the current values and replace afterwards
        /** @var Repository $config */
        $config = config();
        $configValues = $config->all();

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
    public function commandExists(string $command): bool
    {
        return array_key_exists($command, Artisan::all());
    }
}
