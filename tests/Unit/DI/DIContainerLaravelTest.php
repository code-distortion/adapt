<?php

namespace CodeDistortion\Adapt\Tests\Unit\DI;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\LaravelTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test the DIContainer class
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DIContainerLaravelTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used

    /**
     * Provide data for the di_container_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public static function diContainerDataProvider(): array
    {

        return [
            'artisan' => [
                'method' => 'artisan',
                'params' => ['artisan' => new LaravelArtisan()],
            ],
            'db' => [
                'method' => 'db',
                'params' => ['db' => new LaravelDB()],
            ],
            'exec' => [
                'method' => 'exec',
                'params' => ['exec' => new Exec()],
            ],
            'filesystem' => [
                'method' => 'filesystem',
                'params' => ['filesystem' => new Filesystem()],
            ],
            'log' => [
                'method' => 'log',
                'params' => ['log' => new LaravelLog(false, false, 0)],
            ],
        ];
    }

    /**
     * Test that the DIContainer object can set and get values properly.
     *
     * @test
     * @dataProvider diContainerDataProvider
     *
     * @param string  $method The set method to call.
     * @param mixed[] $params The parameters to pass to this set method, and the values to check after.
     * @return void
     */
    #[Test]
    #[DataProvider('diContainerDataProvider')]
    public static function di_container_can_set_and_get_values(string $method, array $params)
    {
        $di = new DIContainer();

        $callable = [$di, $method];
        if (is_callable($callable)) {

            foreach ($params as $name => $value) {
                if (is_callable($value)) {
                    $params[$name] = $value();
                }
            }

            call_user_func_array($callable, $params);
        }

        foreach ($params as $name => $value) {
            self::assertSame($value, $di->$name);
        }
    }
}
