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
    public static function logException($log, $e, $newLineAfter = false)
    {
        if (is_a($e, AdaptRemoteBuildException::class)) {
            $title = $e->generateTitleForLog();
            $lines = $e->generateLinesForLog();
        } else {

            $title = 'An Exception Occurred - ' . Exceptions::readableExceptionClass($e);

            $lines = array_filter(self::breakDownStringLinesIntoArray($e->getMessage()));

            $e2 = $e;
            while ($e2 = $e2->getPrevious()) {
                $message = 'Previous Exception - ' . static::readableExceptionClass($e2) . PHP_EOL . $e2->getMessage();
                $e2Lines = array_filter(self::breakDownStringLinesIntoArray($message));
                $lines = array_merge($lines, [''], $e2Lines);
            }
        }

        $log->logBox($lines, $title, 'error', $newLineAfter);
    }

    /**
     * Generate a readable name for an exception.
     *
     * @param Throwable $e The exception that was thrown.
     * @return string
     */
    public static function readableExceptionClass($e): string
    {
        $exceptionClass = get_class($e);
        if (!is_a($e, AdaptException::class)) {
            return $exceptionClass;
        }

        $temp = explode('\\', $exceptionClass);
        return array_pop($temp);
    }

    /**
     * Break down a string of text with new lines into an array.
     *
     * @param string $string The string to break down.
     * @return string[]
     */
    private static function breakDownStringLinesIntoArray(string $string): array
    {
        $temp = str_replace("\r", "\n", str_replace("\r\n", "\n", $string));
        return explode("\n", $temp);
    }
}
