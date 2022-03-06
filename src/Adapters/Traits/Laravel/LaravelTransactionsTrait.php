<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

/**
 * Database-adapter methods related to building a Laravel database.
 *
 * @see InjectInclHasherTrait
 */
trait LaravelTransactionsTrait
{
    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    protected function laravelApplyTransaction(): void
    {
        $closure = $this->di->dbTransactionClosure;
        if (!is_callable($closure)) {
            return;
        }

        $closure($this->configDTO->connection);
    }
}
