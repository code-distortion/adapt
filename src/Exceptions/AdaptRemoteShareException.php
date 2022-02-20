<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when sharing config and connection details between installations of Adapt.
 */
class AdaptRemoteShareException extends AdaptException
{
    /**
     * The remote and local versions of Adapt aren't compatible.
     *
     * @return self
     */
    public static function versionMismatch(): self
    {
        return new self("The remote and local versions of Adapt aren't compatible. Please upgrade so they match");
    }

    /**
     * Thrown when the local and remote Adapt installations use different session drivers.
     *
     * @return self
     */
    public static function sessionDriverMismatch(string $localSessionDriver, string $callerSessionDriver): self
    {
        return new self(
            "The local session.driver \"$localSessionDriver\" doesn't match the caller's \"$callerSessionDriver\". "
            . "These must match for browser tests"
        );
    }

    /**
     * The ConfigDTO couldn't be built from the payload passed in the request.
     *
     * @return self
     */
    public static function couldNotReadConfigDTO(): self
    {
        return new self("The passed configuration details could not be read");
    }

    /**
     * The RemoteShareDTO couldn't be built from the payload passed in the request.
     *
     * @return self
     */
    public static function couldNotReadRemoteShareDTO(): self
    {
        return new self("The passed remote-sharing details could not be read");
    }

    /**
     * The ResolvedSettingsDTO couldn't be built from the payload passed in the response.
     *
     * @return self
     */
    public static function couldNotReadResolvedSettingsDTO(): self
    {
        return new self("The passed resolved-settings could not be read");
    }
}
