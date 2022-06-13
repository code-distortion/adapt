<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated relating to test-transactions.
 */
class AdaptTransactionException extends AdaptException
{
    /**
     * Thrown when a test committed the test-transaction.
     *
     * @param string $testName The name of the test that committed the transaction.
     * @return self
     */
    public static function aTestCommittedTheWrapperTransaction($testName): self
    {
        return new self(
            "The wrapper-transaction was committed - see "
            . "https://github.com/code-distortion/adapt#testing-code-that-uses-transactions for more details"
        );
    }
}
