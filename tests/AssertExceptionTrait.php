<?php

namespace CodeDistortion\Adapt\Tests;

use Throwable;

/**
 * Add an assertion method that checks whether an exception was thrown or not.
 *
 * @mixin \PHPUnit\Framework\TestCase
 * @mixin \PHPUnit_Framework_TestCase
 */
trait AssertExceptionTrait
{
    /**
     * Check if a callback generates an exception or not, and if so, that the type matches.
     *
     * @param string|null $expectException The exception to expect.
     * @param callable    $callback        The callback to run.
     * @return void
     */
    protected function assertException(?string $expectException, callable $callback): void
    {
        $e = null;
        try {
            $callback();
        } catch (Throwable $e) {
        }

        $thrownException = $e ? get_class($e) : null;

        if ($thrownException && !$expectException) {
            $this->assertTrue(false, "A \"$thrownException\" exception was thrown. None was expected");
        }

        if ($thrownException && $expectException && !is_a($e ?? '', $expectException)) {
            $this->assertTrue(
                false,
                "A \"$expectException\" exception was expected, but \"$thrownException\" was thrown"
            );
        }

        if (!$thrownException && $expectException) {
            $this->assertTrue(false, "A \"$expectException\" exception was expected but none was thrown");
        }

        $this->assertTrue(true);
    }
}
