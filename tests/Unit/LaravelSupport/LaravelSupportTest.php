<?php

namespace CodeDistortion\Adapt\Tests\Unit\LaravelSupport;

use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;

/**
 * Test the LaravelSupport class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelSupportTest extends PHPUnitTestCase
{
    /**
     * Provide data for the test_that_the_correct_seeders_are_picked test below.
     *
     * @return array
     */
    public function seedersDataProvider(): array
    {
        return [
            [true, ['seeders'], true, true, ['config'], ['seeders']],
            [true, ['seeders'], true, true, null, ['seeders']],
            [true, ['seeders'], true, false, ['config'], ['seeders']],
            [true, ['seeders'], true, false, null, ['seeders']],
            [true, ['seeders'], false, null, ['config'], ['seeders']],
            [true, ['seeders'], false, null, null, ['seeders']],

            [true, 'seeders', true, true, ['config'], ['seeders']],
            [true, 'seeders', true, true, null, ['seeders']],
            [true, 'seeders', true, false, ['config'], ['seeders']],
            [true, 'seeders', true, false, null, ['seeders']],
            [true, 'seeders', false, null, ['config'], ['seeders']],
            [true, 'seeders', false, null, null, ['seeders']],

            [false, null, true, true, ['config'], ['Database\\Seeders\\DatabaseSeeder']],
            [false, null, true, true, null, ['Database\\Seeders\\DatabaseSeeder']],
            [false, null, true, false, ['config'], []],
            [false, null, true, false, null, []],

            [false, null, false, null, ['config'], ['config']],
            [false, null, false, null, null, []],
        ];
    }

    /**
     * Test that the CacheListDTO object can set and get values properly.
     *
     * @test
     * @dataProvider seedersDataProvider
     * @param boolean $hasSeedersProp Whether the test has the $seeders property or not.
     * @param mixed   $seedersProp    The $seeders property.
     * @param boolean $hasSeedProp    Whether the test has the $seed property or not.
     * @param mixed   $seedProp       The $seed property.
     * @param mixed   $seedersConfig  The "code_distortion.adapt.seeders" Laravel config value.
     * @return void
     */
    public function test_that_the_correct_seeders_are_picked(
        bool $hasSeedersProp,
        $seedersProp,
        bool $hasSeedProp,
        $seedProp,
        $seedersConfig,
        array $expectedOutcome
    ) {

        $seeders = LaravelSupport::resolveSeeders(
            $hasSeedersProp,
            $seedersProp,
            $hasSeedProp,
            $seedProp,
            $seedersConfig
        );
        $this->assertSame($expectedOutcome, $seeders);
    }
}
