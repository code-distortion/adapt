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
    public static function testCommittedTransaction($testName): self
    {
        return new self(
            "The $testName test committed the transaction wrapper - see "
            . "https://github.com/code-distortion/adapt#testing-code-that-uses-transactions for more details"
        );
    }
}
