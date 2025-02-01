<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\LaravelPropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptPropBagDTOException;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test the PropBag class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelPropBagDTOTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used

    /**
     * Provide data for the prop_bag_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public static function propBagDTODataProvider(): array
    {
        return [
            'set and get' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'prop',
                    'params' => ['defaultConnection'],
                    'expected' => 'mysql',
                    'exception' => null,
                ],
            ],
            'set and get something else' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'prop',
                    'params' => ['somethingElse'],
                    'expected' => '',
                    'exception' => AdaptPropBagDTOException::class,
                ],
            ],
            'set and get with default' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'prop',
                    'params' => ['defaultConnection', 'default'],
                    'expected' => 'mysql',
                    'exception' => null,
                ],
            ],
            'set and get something else with default' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'prop',
                    'params' => ['somethingElse', 'default'],
                    'expected' => 'default',
                    'exception' => null,
                ],
            ],
            'set and hasProp when it does exist' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'hasProp',
                    'params' => ['defaultConnection'],
                    'expected' => true,
                    'exception' => null,
                ],
            ],
            'set and hasProp when it doesn\'t exist' => [
                'set' => [
                    'defaultConnection' => 'mysql',
                ],
                'check' => [
                    'method' => 'hasProp',
                    'params' => ['somethingElse'],
                    'expected' => false,
                    'exception' => null,
                ],
            ],
        ];
    }

    /**
     * Test that the PropBagDTO object can set and get values properly.
     *
     * @test
     * @dataProvider propBagDTODataProvider
     *
     * @param string[]            $set   The values to set.
     * @param array<string,mixed> $check Attempts to get values back out and check the result.
     * @return void
     * @throws Throwable Any exception that wasn't expected by the test.
     */
    #[Test]
    #[DataProvider('propBagDTODataProvider')]
    public static function test_that_prop_bag_dto_can_set_and_get_values(array $set, array $check)
    {
        // add some values to the bag
        $propBag = new LaravelPropBagDTO();
        foreach ($set as $name => $value) {
            self::assertSame(
                $propBag,
                $propBag->addProp($name, $value)
            );
        }

        // retrieve some values and see what happens
        $callable = [$propBag, $check['method']];
        if (!is_callable($callable)) {
            return;
        }

        /** @var array<int|string, mixed> $params */
        $params = $check['params'];

        if (is_string($check['exception'])) {

            try {
                call_user_func_array($callable, $params);
            } catch (Throwable $e) {
                if (!$e instanceof $check['exception']) {
                    throw $e;
                }
                self::assertTrue(true);
            }

        } else {
            $value = call_user_func_array($callable, $params);
            self::assertSame($check['expected'], $value);
        }
    }

    /**
     * Test the LaravelPropBagDTO->config(..) method.
     *
     * @test
     *
     * @return void
     */
    public static function test_the_config_getter()
    {
        config(['code_distortion.adapt.existing_value' => 'config value']);
        $propBag = (new LaravelPropBagDTO())->addProp('existingValue', 'prop value');
        self::assertSame(null, $propBag->adaptConfig('missing_value', 'missingValue'));
        self::assertSame('prop value', $propBag->adaptConfig('missing_value', 'existingValue'));
        self::assertSame('config value', $propBag->adaptConfig('existing_value', 'missingValue'));
        self::assertSame('prop value', $propBag->adaptConfig('existing_value', 'existingValue'));
    }
}
