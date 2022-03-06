<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\Exceptions\AdaptException;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteBuildException;
use Throwable;

/**
 * Support methods relating to exceptions.
 */
class Exceptions
{
    /**
     * Generate and log the exception message.
     *
     * @param LogInterface $log          The log object to log with.
     * @param Throwable    $e            The exception to log.
     * @param boolean      $newLineAfter Add a new line afterwards?.
     * @return void
     */
    public static function logException(LogInterface $log, Throwable $e, bool $newLineAfter = false): void
    {
        if (is_a($e, AdaptRemoteBuildException::class)) {
            $lines = $e->generateLinesForLog();
            $title = $e->generateTitleForLog();
        } else {
            $lines = array_filter([$e->getMessage()]);
            $title = 'An Exception Occurred - ' . Exceptions::readableExceptionClass($e);
        }

        $log->logBox($lines, $title, 'error', $newLineAfter);
    }

    /**
     * Generate a readable name for an exception.
     *
     * @param Throwable $e The exception that was thrown.
     * @return string
     */
    public static function readableExceptionClass(Throwable $e): string
    {
        $exceptionClass = get_class($e);
        if (!is_a($e, AdaptException::class)) {
            return $exceptionClass;
        }

        $temp = explode('\\', $exceptionClass);
        return array_pop($temp);
    }
}
