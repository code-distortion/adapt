<?php

namespace CodeDistortion\Adapt\DI\Injectable;

/**
 * Injectable class to abstract program execution.
 */
class Exec
{
    /**
     * Execute the given command.
     *
     * @param string $command   The command to run.
     * @param mixed  $output    Will be populated with the script's output.
     * @param mixed  $returnVal Will contain the script's exit-code.
     * @return string
     */
    public function run(string $command, &$output, &$returnVal): string
    {
        return exec($command, $output, $returnVal);
    }

    /**
     * Run the given command and see if it completes without error.
     *
     * @param string $command The command to run.
     * @return boolean
     */
    public function commandRuns(string $command): bool
    {
        exec($command.' 2>/dev/null', $output, $returnVal); // suppress stderror
        return $returnVal == 0;
    }
}
