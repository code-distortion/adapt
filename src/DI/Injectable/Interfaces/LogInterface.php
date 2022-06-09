<?php

namespace CodeDistortion\Adapt\DI\Injectable\Interfaces;

/**
 * Injectable class to abstract logging actions.
 */
interface LogInterface
{
    /**
     * Get the current verbosity level.
     *
     * @return integer
     */
    public function currentVerbosity(): int;



    /**
     * Display some debug output - DEBUG level - verbose 0.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function debug(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - DEBUG level - verbose 1.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vDebug(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - DEBUG level - verbose 2.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vvDebug(string $message, int $timerRef = null, bool $newLineAfter = false): void;



    /**
     * Display some debug output - WARNING level - verbose 0.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function warning(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - WARNING level - verbose 1.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vWarning(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - WARNING level - verbose 2.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vvWarning(string $message, int $timerRef = null, bool $newLineAfter = false): void;



    /**
     * Display some debug output - ERROR level - verbose 0.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function error(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - ERROR level - verbose 1.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vError(string $message, int $timerRef = null, bool $newLineAfter = false): void;

    /**
     * Display some debug output - ERROR level - verbose 2.
     *
     * @param string       $message      The message to show.
     * @param integer|null $timerRef     Show the time taken for the given timer.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function vvError(string $message, int $timerRef = null, bool $newLineAfter = false): void;



    /**
     * Create a new timer and return a reference to it.
     *
     * @return integer
     */
    public function newTimer(): int;



    /**
     * Add the array keys to the values, padded based on the length of the longest key.
     *
     * @param array<string, string> $lines The lines to process.
     * @return string[]
     */
    public function padList(array $lines): array;

    /**
     * Log some lines in a box.
     *
     * @param string|string[] $lines        The lines to log in a table.
     * @param string|null     $title        The title to add to the top line.
     * @param string          $level        The logging level to use.
     * @param boolean         $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public function logBox($lines, ?string $title = null, string $level = 'debug', bool $newLineAfter = false): void;
}
