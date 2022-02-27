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
     * @param Throwable $e The exception to log.
     * @return void
     */
    public static function logException(LogInterface $log, Throwable $e): void
    {
        if (is_a($e, AdaptRemoteBuildException::class)) {
            $lines = $e->generateLinesForLog();
            $title = $e->generateTitleForLog();
        } else {
            $lines = array_filter([$e->getMessage()]);
            $title = 'An Exception Occurred - ' . Exceptions::resolveExceptionClass($e);
        }

        $log->logBox($lines, $title, 'error');
    }

    /**
     * Resolve a readable name for an exception.
     *
     * @param Throwable $e The exception that was thrown.
     * @return string
     */
    public static function resolveExceptionClass(Throwable $e): string
    {
        $exceptionClass = get_class($e);
        if (!is_a($e, AdaptException::class)) {
            return $exceptionClass;
        }

        $temp = explode('\\', $exceptionClass);
        return array_pop($temp);
    }
}
