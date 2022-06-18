<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when booting a builder.
 */
class AdaptBrowserTestException extends AdaptException
{
    /**
     * Thrown when a sharable config file could not be written to.
     *
     * @param string $path The path to the sharable config file.
     * @return self
     */
    public static function sharableConfigFileNotSaved($path): self
    {
        return new self("The sharable config file \"$path\" could not be created");
    }

    /**
     * Thrown when a sharable config file could not be read from.
     *
     * @param string         $path              The path to the sharable config file.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function sharableConfigFileNotLoaded($path, $previousException = null): self
    {
        return $previousException
            ? new self("The sharable config file \"$path\" could not be loaded", 0, $previousException)
            : new self("The sharable config file \"$path\" could not be loaded");
    }
}
