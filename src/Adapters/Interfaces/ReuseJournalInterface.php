<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptJournalException;

/**
 * Database-adapter methods related to managing reuse through journaling.
 */
interface ReuseJournalInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer       $di        The dependency-injection container to use.
     * @param ConfigDTO         $configDTO A DTO containing the settings to use.
     * @param VerifierInterface $verifier  The verifier, used to get primary-keys and verify database structure.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO, VerifierInterface $verifier);



    /**
     * Determine if a journal can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsJournaling(): bool;

    /**
     * Create journal tables and triggers to populate them, for each table.
     *
     * @return void
     * @throws AdaptJournalException When something goes wrong.
     */
    public function setUpJournal();

    /**
     * Record that journaling has begun.
     *
     * This is run automatically if setUpJournaling() above is called, but can be called directly here (when the
     * database was built remotely) so the journaling tables aren't set-up again.
     *
     * @return void
     */
    public function recordJournalingStart();

    /**
     * Take the journal information and "undo" the changes.
     *
     * @param boolean $newLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    public function reverseJournal($newLineAfter);
}
