<?php

namespace CodeDistortion\Adapt\Adapters\AbstractClasses;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use stdClass;

/**
 * Database-adapter methods related to managing reuse through transactions.
 */
abstract class AbstractReuseTransaction implements ReuseTransactionInterface
{
    use InjectTrait;



    /**
     * Check if the transaction was rolled-back.
     *
     * (to be run before Adapt rolls back the transaction).
     *
     * @return boolean
     */
    public function wasTransactionRolledBack(): bool
    {
        return $this->loadTransactionReusable() === 1;
    }

    /**
     * Check if the transaction was committed.
     *
     * @return boolean
     */
    public function wasTransactionCommitted(): bool
    {
        return $this->loadTransactionReusable() === 0;
    }

    /**
     * Load the transaction-reusable value from Adapt's reuse-info table.
     *
     * @return integer|null
     */
    abstract protected function loadTransactionReusable();
}
