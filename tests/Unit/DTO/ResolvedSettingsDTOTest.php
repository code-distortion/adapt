<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\ResolvedSettingsDTO;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the ResolvedSettingsDTO class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class ResolvedSettingsDTOTest extends PHPUnitTestCase
{
    /**
     * Provide data for the resolved_settings_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public static function resolvedSettingsDtoDataProvider(): array
    {
        return [
            'projectName' => [
                'method' => 'projectName',
                'params' => ['projectName' => 'my-project'],
            ],

            'testName' => [
                'method' => 'testName',
                'params' => ['testName' => 'some-test'],
            ],

            'connection' => [
                'method' => 'connection',
                'params' => ['connection' => 'mysql'],
            ],

            'driver' => [
                'method' => 'driver',
                'params' => ['driver' => 'mysql'],
            ],

            'host 1' => [
                'method' => 'host',
                'params' => ['host' => 'localhost'],
            ],
            'host 2' => [
                'method' => 'host',
                'params' => ['host' => null],
            ],

            'database 1' => [
                'method' => 'database',
                'params' => ['database' => 'my_db'],
            ],
            'database 2' => [
                'method' => 'database',
                'params' => ['database' => null],
            ],

            'builtRemotely 1' => [
                'method' => 'builtRemotely',
                'params' => [
                    'builtRemotely' => true,
                    'remoteBuildUrl' => 'https://some-other-host/',
                ],
            ],
            'builtRemotely 2' => [
                'method' => 'builtRemotely',
                'params' => [
                    'builtRemotely' => false,
                    'remoteBuildUrl' => null,
                ],
            ],

            'snapshotType 1' => [
                'method' => 'snapshotType',
                'params' => [
                    'resolvedSnapshotType' => 'afterMigrations',
                    'reuseDBSnapshotType' => 'afterSeeders',
                    'notReuseDBSnapshotType' => 'both',
                ],
            ],
            'snapshotType 2' => [
                'method' => 'snapshotType',
                'params' => [
                    'resolvedSnapshotType' => null,
                    'reuseDBSnapshotType' => null,
                    'notReuseDBSnapshotType' => null,
                ],
            ],

            'storageDir' => [
                'method' => 'storageDir',
                'params' => ['storageDir' => '/path/to/dir'],
            ],

            'initialImports' => [
                'method' => 'initialImports',
                'params' => ['initialImports' => ['mysql' => ['a.sql', 'b.sql']]],
            ],

            'migrations 1' => [
                'method' => 'migrations',
                'params' => ['migrations' => true],
            ],
            'migrations 2' => [
                'method' => 'migrations',
                'params' => ['migrations' => '/path/to/dir'],
            ],

            'seeders 1' => [
                'method' => 'seeders',
                'params' => [
                    'isSeedingAllowed' => true,
                    'seeders' => ['DatabaseSeederA', 'DatabaseSeederB'],
                ],
            ],
            'seeders 2' => [
                'method' => 'seeders',
                'params' => [
                    'isSeedingAllowed' => false,
                    'seeders' => ['DatabaseSeederA', 'DatabaseSeederB'],
                ],
                'outcome' => [
                    'isSeedingAllowed' => false,
                    'seeders' => [],
                ],
            ],
            'seeders 3' => [
                'method' => 'seeders',
                'params' => [
                    'isSeedingAllowed' => false,
                    'seeders' => [],
                ],
            ],

            'scenarios 1' => [
                'method' => 'scenarios',
                'params' => [
                    'usingScenarios' => true,
                    'buildChecksum' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'snapshotChecksum' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                    'scenarioChecksum' => 'cccccccccccccccccccccccccccccccc',
                ],
            ],
            'scenarios 2' => [
                'method' => 'scenarios',
                'params' => [
                    'usingScenarios' => true,
                    'buildChecksum' => null,
                    'snapshotChecksum' => null,
                    'scenarioChecksum' => null,
                ],
            ],
            'scenarios 3' => [
                'method' => 'scenarios',
                'params' => [
                    'usingScenarios' => false,
                    'buildChecksum' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'snapshotChecksum' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                    'scenarioChecksum' => 'cccccccccccccccccccccccccccccccc',
                ],
                'outcome' => [
                    'usingScenarios' => false,
                    'buildChecksum' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
                    'snapshotChecksum' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
                    'scenarioChecksum' => 'cccccccccccccccccccccccccccccccc',
                ],
            ],

            'isBrowserTest' => [
                'method' => 'isBrowserTest',
                'params' => ['isBrowserTest' => true],
            ],

            'sessionDriver' => [
                'method' => 'sessionDriver',
                'params' => ['sessionDriver' => 'database'],
            ],

            'transactionReusable' => [
                'method' => 'transactionReusable',
                'params' => ['transactionReusable' => true],
            ],

            'journalReusable' => [
                'method' => 'journalReusable',
                'params' => ['journalReusable' => true],
            ],

            'verifyDatabase' => [
                'method' => 'verifyDatabase',
                'params' => ['verifyDatabase' => true],
            ],

            'forceRebuild' => [
                'method' => 'forceRebuild',
                'params' => ['forceRebuild' => true],
            ],

            'databaseWasReused' => [
                'method' => 'databaseWasReused',
                'params' => ['databaseWasReused' => true],
            ],
        ];
    }

    /**
     * Test that the ResolvedSettingsDTO object can set and get values properly.
     *
     * @test
     * @dataProvider resolvedSettingsDtoDataProvider
     *
     * @param string       $method  The set method to call.
     * @param mixed[]      $params  The parameters to pass to this set method, and the values to check after.
     * @param mixed[]|null $outcome The outcome values to check for (uses $params if not given).
     * @return void
     */
    #[Test]
    #[DataProvider('resolvedSettingsDtoDataProvider')]
    public static function resolved_settings_dto_can_set_and_get_values(
        string $method,
        array $params,
        $outcome = null
    ) {

        $configDTO = new ResolvedSettingsDTO();

        $callable = [$configDTO, $method];
        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
        }

        $outcome = $outcome ?? $params;
        foreach ($outcome as $name => $value) {
            self::assertSame($value, $configDTO->$name);
        }
    }
}
