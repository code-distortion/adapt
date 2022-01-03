<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when booting a builder.
 */
class AdaptBrowserTestException extends AdaptException
{
    /**
     * Thrown when a temporary config file could not be written to.
     *
     * @param string $path The path to the temporary config file.
     * @return self
     */
    public static function tempConfigFileNotSaved($path): self
    {
        return new self("The temporary config file \"$path\" could not be created");
    }

    /**
     * Thrown when a temporary config file could not be read from.
     *
     * @param string         $path The path to the temporary config file.
     * @param Throwable|null $e    The original exception (if relevant).
     * @return self
     */
    public static function tempConfigFileNotLoaded($path, $e = null): self
    {
        return $e
            ? new self("The temporary config file \"$path\" could not be loaded", 0, $e)
            : new self("The temporary config file \"$path\" could not be loaded");
    }
}
