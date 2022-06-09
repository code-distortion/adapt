<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Database-adapter methods related to managing reuse through transactions.
 */
interface ReuseTransactionInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Determine if a transaction can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsTransactions(): bool;

    /**
     * Start the wrapper-transaction.
     *
     * @return void
     */
    public function startTransaction(): void;

    /**
     * Roll-back the wrapper-transaction.
     *
     * @return void
     */
    public function rollBackTransaction(): void;

    /**
     * Check if the transaction was committed.
     *
     * @return boolean
     */
    public function wasTransactionCommitted(): bool;
}
