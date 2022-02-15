<?php

namespace CodeDistortion\Adapt\DI\Injectable\Interfaces;

/**
 * Injectable class to abstract logging actions.
 */
interface LogInterface
{
    /**
     * Display some debug output - INFO level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function info(string $message, int $timerRef = null): void;

    /**
     * Display some debug output - WARNING level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function warning(string $message, int $timerRef = null): void;

    /**
     * Display some debug output - ERROR level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function error(string $message, int $timerRef = null): void;

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
    public function getDuration(int $timerRef = null): ?float;
}
