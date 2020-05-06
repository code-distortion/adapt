<?php

namespace CodeDistortion\Adapt\Tests;

// phpunit added namespacing to it's classes in >= 5.5
// this code compensates for the fact that older versions of Laravel Orchestra Testbench (which are also part of the
// test suite) require an older version of phpunit
if (class_exists(\PHPUnit\Framework\TestCase::class)) {
    class_alias(\PHPUnit\Framework\TestCase::class, TestCase::class);
} else {
    class_alias(\PHPUnit_Framework_TestCase::class, TestCase::class);
}

//use PHPUnit\Framework\TestCase;

/**
 * The test case that unit tests extend from.
 */
class PHPUnitTestCase extends TestCase
{
}
