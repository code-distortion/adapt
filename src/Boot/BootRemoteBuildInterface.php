<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;

/**
 * Bootstrap Adapt to build a database remotely.
 */
interface BootRemoteBuildInterface
{
    /**
     * Ensure the storage-directory exists.
     *
     * @return static
     * @throws AdaptConfigException When the storage directory cannot be created.
     */
    public function ensureStorageDirExists(): self;

    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param ConfigDTO $remoteConfig The config from the remote Adapt installation.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder(ConfigDTO $remoteConfig): DatabaseBuilder;

    /**
     * Check that the session.driver matches during browser tests.
     *
     * @param ConfigDTO $remoteConfigDTO    The caller's ConfigDTO.
     * @param string    $localSessionDriver The local session driver.
     * @return void
     * @throws AdaptRemoteShareException When the session.driver doesn't match during browser tests.
     */
    public function ensureSessionDriversMatchDuringBrowserTests(
        ConfigDTO $remoteConfigDTO,
        string $localSessionDriver
    ): void;
}
