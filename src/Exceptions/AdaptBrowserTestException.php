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
    public static function tempConfigFileNotSaved(string $path): self
    {
        return new self("The temporary config file \"$path\" could not be created");
    }

    /**
     * Thrown when a temporary config file could not be read from.
     *
     * @param string         $path              The path to the temporary config file.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function tempConfigFileNotLoaded(string $path, ?Throwable $previousException = null): self
    {
        return $previousException
            ? new self("The temporary config file \"$path\" could not be loaded", 0, $previousException)
            : new self("The temporary config file \"$path\" could not be loaded");
    }
}
