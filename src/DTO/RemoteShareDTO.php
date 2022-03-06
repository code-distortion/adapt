<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\DTO\Traits\DTOBuildTrait;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * The details that get shared between installations to convey which databases / config settings to use.
 */
class RemoteShareDTO
{
    use DTOBuildTrait;

    /**
     * The RemoteShareDTO version. An exception will be thrown when there's a mismatch between installations of Adapt.
     *
     * @var integer
     */
    public int $dtoVersion;

    /** @var string|null The location of the temporary config file. */
    public ?string $tempConfigPath;

    /** @var array<string, string> The connections and the names of their prepared databases. */
    public array $connectionDBs;



    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->version(Settings::REMOTE_SHARE_DTO_VERSION);
    }



    /**
     * Set the remote-share version.
     *
     * @param integer $version The name of this project.
     * @return static
     */
    public function version(int $version): self
    {
        $this->dtoVersion = $version;
        return $this;
    }

    /**
     * Set the temp-config-path, the location of the temporary config file.
     *
     * @param string|null $tempConfigPath The path to the temporary config file.
     * @return static
     */
    public function tempConfigFile(?string $tempConfigPath): self
    {
        $this->tempConfigPath = $tempConfigPath;
        return $this;
    }

    /**
     * Set the list of connections and their prepared databases.
     *
     * @param array<string, string> $connectionDBs The connections dbs.
     * @return static
     */
    public function connectionDBs(array $connectionDBs): self
    {
        $this->connectionDBs = $connectionDBs;
        return $this;
    }



    /**
     * Build a new RemoteShareDTO from the data given in a request to share database info remotely.
     *
     * @param string $payload The raw RemoteShareDTO data from the request.
     * @return self|null
     * @throws AdaptRemoteShareException When the version doesn't match.
     */
    public static function buildFromPayload(string $payload): ?self
    {
        if (!mb_strlen($payload)) {
            return null;
        }

        $json = base64_decode($payload, true);
        if (!is_string($json)) {
            throw AdaptRemoteShareException::couldNotReadConfigDTO();
        }

        $values = json_decode($json, true);
        if (!is_array($values)) {
            throw AdaptRemoteShareException::couldNotReadConfigDTO();
        }

        $remoteShareDTO = static::buildFromArray($values);

        if ($remoteShareDTO->dtoVersion != Settings::REMOTE_SHARE_DTO_VERSION) {
            throw AdaptRemoteShareException::versionMismatch();
        }

        return $remoteShareDTO;
    }

    /**
     * Build the value to send in requests.
     *
     * @return string
     */
    public function buildPayload(): string
    {
        return base64_encode((string) json_encode(get_object_vars($this)));
    }
}
