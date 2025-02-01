<?php

namespace CodeDistortion\Adapt\Tests\Unit\LaravelSupport;

use CodeDistortion\Adapt\Support\LaravelSupport;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
     * @return mixed[][]
     */
    public static function seedersDataProvider(): array
    {
        return [
            [true, ['seeders'], true, 'seeder', true, true, ['config'], ['seeders']],
            [true, ['seeders'], true, 'seeder', true, true, null, ['seeders']],
            [true, ['seeders'], true, 'seeder', true, false, ['config'], ['seeders']],
            [true, ['seeders'], true, 'seeder', true, false, null, ['seeders']],
            [true, ['seeders'], true, 'seeder', false, null, ['config'], ['seeders']],
            [true, ['seeders'], true, 'seeder', false, null, null, ['seeders']],

            [true, ['seeders'], false, null, true, true, ['config'], ['seeders']],
            [true, ['seeders'], false, null, true, true, null, ['seeders']],
            [true, ['seeders'], false, null, true, false, ['config'], ['seeders']],
            [true, ['seeders'], false, null, true, false, null, ['seeders']],
            [true, ['seeders'], false, null, false, null, ['config'], ['seeders']],
            [true, ['seeders'], false, null, false, null, null, ['seeders']],

            [true, 'seeders', true, 'seeder', true, true, ['config'], ['seeders']],
            [true, 'seeders', true, 'seeder', true, true, null, ['seeders']],
            [true, 'seeders', true, 'seeder', true, false, ['config'], ['seeders']],
            [true, 'seeders', true, 'seeder', true, false, null, ['seeders']],
            [true, 'seeders', true, 'seeder', false, null, ['config'], ['seeders']],
            [true, 'seeders', true, 'seeder', false, null, null, ['seeders']],

            [true, 'seeders', false, null, true, true, ['config'], ['seeders']],
            [true, 'seeders', false, null, true, true, null, ['seeders']],
            [true, 'seeders', false, null, true, false, ['config'], ['seeders']],
            [true, 'seeders', false, null, true, false, null, ['seeders']],
            [true, 'seeders', false, null, false, null, ['config'], ['seeders']],
            [true, 'seeders', false, null, false, null, null, ['seeders']],

            [false, null, true, 'seeder', true, true, ['config'], ['seeder']],
            [false, null, true, 'seeder', true, true, null, ['seeder']],
            [false, null, true, 'seeder', true, false, ['config'], []],
            [false, null, true, 'seeder', true, false, null, []],

            [false, null, false, null, true, true, ['config'], ['Database\\Seeders\\DatabaseSeeder']],
            [false, null, false, null, true, true, null, ['Database\\Seeders\\DatabaseSeeder']],
            [false, null, false, null, true, false, ['config'], []],
            [false, null, false, null, true, false, null, []],

            [false, null, true, 'seeder', false, null, ['config'], ['config']],
            [false, null, true, 'seeder', false, null, null, []],

            [false, null, false, null, false, null, ['config'], ['config']],
            [false, null, false, null, false, null, null, []],
        ];
    }

    /**
     * Test that the seeder settings get picked up properly.
     *
     * @test
     * @dataProvider seedersDataProvider
     *
     * @param boolean  $hasSeedersProp  Whether the test has the $seeders property or not.
     * @param mixed    $seedersProp     The $seeders property.
     * @param boolean  $hasSeederProp   Whether the test has the $seeder property or not.
     * @param mixed    $seederProp      The $seeder property.
     * @param boolean  $hasSeedProp     Whether the test has the $seed property or not.
     * @param mixed    $seedProp        The $seed property.
     * @param mixed    $seedersConfig   The "code_distortion.adapt.seeders" Laravel config value.
     * @param string[] $expectedOutcome The expected seeders.
     * @return void
     */
    #[Test]
    #[DataProvider('seedersDataProvider')]
    public static function test_that_the_correct_seeders_are_picked(
        bool $hasSeedersProp,
        $seedersProp,
        bool $hasSeederProp,
        $seederProp,
        bool $hasSeedProp,
        $seedProp,
        $seedersConfig,
        array $expectedOutcome
    ) {

        $seeders = LaravelSupport::resolveSeeders(
            $hasSeedersProp,
            $seedersProp,
            $hasSeederProp,
            $seederProp,
            $hasSeedProp,
            $seedProp,
            $seedersConfig
        );
        self::assertSame($expectedOutcome, $seeders);
    }
}
