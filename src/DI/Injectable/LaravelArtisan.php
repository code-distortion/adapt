<?php

namespace CodeDistortion\Adapt\DI\Injectable;

use Artisan;
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
    public function call(string $command, array $parameters = [], $outputBuffer = null): int
    {
        return Artisan::call($command, $parameters, $outputBuffer);
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
