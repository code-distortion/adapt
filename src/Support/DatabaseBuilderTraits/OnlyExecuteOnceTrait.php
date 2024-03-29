<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;

trait OnlyExecuteOnceTrait
{
    /** @var boolean Whether this builder has been executed yet or not. */
    private $executed = false;



    /**
     * Make sure this object is only "executed" once.
     *
     * @return void
     * @throws AdaptBuildException When this object is executed more than once.
     */
    private function onlyExecuteOnce()
    {
        if ($this->hasExecuted()) {
            throw AdaptBuildException::databaseBuilderAlreadyExecuted();
        }
        $this->executed = true;
    }

    /**
     * Return whether this object has been executed yet.
     *
     * @return boolean
     */
    public function hasExecuted(): bool
    {
        return $this->executed;
    }
}
