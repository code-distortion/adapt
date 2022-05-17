<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptJournalException;

/**
Database-adapter methods related to verifying a database's structure and content.
 */
interface VerifierInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Determine whether this database can be verified or not (for checking of database structure and content).
     *
     * @return boolean
     */
    public function supportsVerification(): bool;

    /**
     * Create and populate the verification table.
     *
     * @param boolean $createStructureHash Generate hashes of the create-table queries?.
     * @param boolean $createDataHash      Generate hashes of the table's data?.
     * @return void
     * @throws AdaptJournalException When something goes wrong.
     */
    public function setUpVerification(bool $createStructureHash, bool $createDataHash): void;

    /**
     * Record that a test with verification has begun.
     *
     * @return void
     */
    public function recordVerificationStart(): void;

    /**
     * Record that a test with verification has finished, and the database is clean.
     *
     * @return void
     */
    public function recordVerificationStop(): void;

    /**
     * Verify that the database's structure hasn't changed.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    public function verifyStructure(bool $newLineAfter): void;

    /**
     * Verify that the database's content hasn't changed.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    public function verifyData(bool $newLineAfter): void;

    /**
     * Load the CREATE TABLE query for a particular table from the database.
     *
     * @param string  $table        The table to generate the query for.
     * @param boolean $forceRefresh Will overwrite the internal cache when true.
     * @return string
     */
    public function getCreateTableQuery(string $table, bool $forceRefresh = false): string;

    /**
     * Generate a list of the tables that exist.
     *
     * (Excludes all Adapt tables).
     *
     * @param boolean $forceRefresh Will overwrite the internal cache when true.
     * @return string[]
     */
    public function getTableList(bool $forceRefresh = false): array;

    /**
     * Get a table's primary-key.
     *
     * Note: returned as an array, which may contain more than one field.
     * Note: may return the first unique key instead if a primary-key doesn't exist.
     *
     * @param string $table The table to get the primary-key for.
     * @return string[]
     */
    public function getPrimaryKey(string $table): array;
}
