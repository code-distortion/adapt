<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;

/**
 * Database-adapter methods related to naming database things.
 */
interface NameInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Build a scenario database name.
     *
     * @param boolean     $usingScenarios     Whether scenarios are being used or not.
     * @param string|null $dbNameChecksumPart The current database part, based on the snapshot checksum.
     * @return string
     * @throws AdaptBuildException When the database name is invalid.
     */
    public function generateDBName($usingScenarios, $dbNameChecksumPart): string;

    /**
     * Generate the path (including filename) for the snapshot file.
     *
     * @param string $snapshotFilenameChecksumPart The current filename part, based on the snapshot checksum.
     * @return string
     */
    public function generateSnapshotPath($snapshotFilenameChecksumPart): string;
}
