<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

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
    public function ensureStorageDirExists();

    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param ConfigDTO $remoteConfig The config from the remote Adapt installation.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder($remoteConfig): DatabaseBuilder;
}
