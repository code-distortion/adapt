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
     * @param callable    $callback        The callback to run.
     * @param string|null $expectException The exception to expect.
     * @return void
     */
    protected static function assertException(callable $callback, $expectException = null)
    {
        $e = null;
        try {
            $callback();
        } catch (Throwable $e) {
        }

        $thrownException = $e ? get_class($e) : null;

        if ($thrownException && !$expectException) {
            self::assertTrue(false, "A \"$thrownException\" exception was thrown. None was expected");
        }

        if ($thrownException && $expectException && !is_a($e ?? '', $expectException)) {
            self::assertTrue(
                false,
                "A \"$expectException\" exception was expected, but \"$thrownException\" was thrown"
            );
        }

        if (!$thrownException && $expectException) {
            self::assertTrue(false, "A \"$expectException\" exception was expected but none was thrown");
        }

        self::assertTrue(true);
    }
}
