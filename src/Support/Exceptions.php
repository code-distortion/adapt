<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Exceptions\AdaptException;
use Throwable;

/**
 * Support methods relating to exceptions.
 */
class Exceptions
{
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
