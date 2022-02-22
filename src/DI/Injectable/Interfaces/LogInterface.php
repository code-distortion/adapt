<?php

namespace CodeDistortion\Adapt\DI\Injectable\Interfaces;

/**
 * Injectable class to abstract logging actions.
 */
interface LogInterface
{
    /**
     * Display some debug output - DEBUG level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function debug($message, $timerRef = null);

    /**
     * Display some debug output - WARNING level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function warning($message, $timerRef = null);

    /**
     * Display some debug output - ERROR level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function error($message, $timerRef = null);

    /**
     * Create a new timer and return a reference to it.
     *
     * @return integer
     */
    public function newTimer(): int;

    /**
     * Return the duration of a timer.
     *
     * @param integer|null $timerRef The timer to get the time taken from.
     * @return float|null
     */
    public function getDuration($timerRef = null);



    /**
     * Add the array keys to the values, padded based on the length of the longest key.
     *
     * @param array<string, string> $lines The lines to process.
     * @return void
     */
    public function padList($lines): array;

    /**
     * Log some lines in a box.
     *
     * @param string|string[] $lines The lines to log in a table.
     * @param string|null     $title The title to add to the top line.
     * @param string          $level The logging level to use.
     * @return void
     */
    public function logBox($lines, $title = null, $level = 'debug');
}
