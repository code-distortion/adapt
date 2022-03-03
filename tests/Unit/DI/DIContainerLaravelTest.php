<?php

namespace CodeDistortion\Adapt\Tests\Unit\DI;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
use CodeDistortion\Adapt\Tests\LaravelTestCase;

/**
 * Test the DIContainer class
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DIContainerLaravelTest extends LaravelTestCase
{
    /**
     * Provide data for the di_container_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function diContainerDataProvider(): array
    {
        $dbTransactionClosure = function () {
        };

        return [
            'artisan' => [
                'method' => 'artisan',
                'params' => ['artisan' => new LaravelArtisan()],
            ],
            'db' => [
                'method' => 'db',
                'params' => ['db' => new LaravelDB()],
            ],
            'dbTransactionClosure' => [
                'method' => 'dbTransactionClosure',
                'params' => [
                    'dbTransactionClosure' => function () use ($dbTransactionClosure) {
                        return $dbTransactionClosure;
                    }
                ],
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
                'params' => ['log' => new LaravelLog(false, false)],
            ],
        ];
    }

    /**
     * Test that the DIContainer object can set and get values properly.
     *
     * @test
     * @dataProvider diContainerDataProvider
     * @param string  $method The set method to call.
     * @param mixed[] $params The parameters to pass to this set method, and the values to check after.
     * @return void
     */
    public function di_container_can_set_and_get_values(string $method, array $params): void
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
            $this->assertSame($value, $di->$name);
        }
    }
}
