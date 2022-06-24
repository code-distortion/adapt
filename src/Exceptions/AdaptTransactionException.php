<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated relating to test-transactions.
 */
class AdaptTransactionException extends AdaptException
{
    /**
     * Thrown when a test rolled-back the test-transaction.
     *
     * @return self
     */
    public static function aTestRolledBackTheWrapperTransaction(): self
    {
        return new self(
            "The wrapper-transaction was rolled-back - see "
            . "https://github.com/code-distortion/adapt#testing-code-that-uses-transactions for more details"
        );
    }

    /**
     * Thrown when a test committed the test-transaction.
     *
     * @return self
     */
    public static function aTestCommittedTheWrapperTransaction(): self
    {
        return new self(
            "The wrapper-transaction was committed - see "
            . "https://github.com/code-distortion/adapt#testing-code-that-uses-transactions for more details"
        );
    }
}
