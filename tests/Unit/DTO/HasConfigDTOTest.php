<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use CodeDistortion\Adapt\Tests\Unit\DTO\Support\HasConfigDTOClass;

/**
 * Test the HasConfigDTO trait.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class HasConfigDTOTest extends PHPUnitTestCase
{
    use DatabaseBuilderTestTrait;

    /**
     * Provide data for the has_config_dto_trait_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function configDtoDataProvider(): array
    {
        return [
            'databaseModifier 1' => [
                'method' => 'databaseModifier',
                'params' => ['1'],
                'outcome' => [
                    'databaseModifier' => '1',
                ],
            ],
            'databaseModifier 2' => [
                'method' => 'databaseModifier',
                'params' => ['2'],
                'outcome' => [
                    'databaseModifier' => '2',
                ],
            ],
            'noDatabaseModifier' => [
                'method' => 'noDatabaseModifier',
                'params' => [],
                'outcome' => [
                    'databaseModifier' => '',
                ],
            ],

            'checkForSourceChanges 1' => [
                'method' => 'checkForSourceChanges',
                'params' => [true],
                'outcome' => [
                    'checkForSourceChanges' => true,
                ],
            ],
            'checkForSourceChanges 2' => [
                'method' => 'checkForSourceChanges',
                'params' => [false],
                'outcome' => [
                    'checkForSourceChanges' => false,
                ],
            ],
            'dontCheckForSourceChanges' => [
                'method' => 'dontCheckForSourceChanges',
                'params' => [],
                'outcome' => [
                    'checkForSourceChanges' => false,
                ],
            ],

            'preMigrationImports 1' => [
                'method' => 'preMigrationImports',
                'params' => [['a']],
                'outcome' => [
                    'preMigrationImports' => ['a'],
                ],
            ],
            'preMigrationImports 2' => [
                'method' => 'preMigrationImports',
                'params' => [[]],
                'outcome' => [
                    'preMigrationImports' => [],
                ],
            ],
            'noPreMigrationImports' => [
                'method' => 'noPreMigrationImports',
                'params' => [],
                'outcome' => [
                    'preMigrationImports' => [],
                ],
            ],

            'migrations 1' => [
                'method' => 'migrations',
                'params' => [true],
                'outcome' => [
                    'migrations' => true,
                ],
            ],
            'migrations 2' => [
                'method' => 'migrations',
                'params' => [false],
                'outcome' => [
                    'migrations' => false,
                ],
            ],
            'migrations 3' => [
                'method' => 'migrations',
                'params' => ['a'],
                'outcome' => [
                    'migrations' => 'a',
                ],
            ],
            'noMigrations' => [
                'method' => 'noMigrations',
                'params' => [],
                'outcome' => [
                    'migrations' => false,
                ],
            ],

            'seeders 1' => [
                'method' => 'seeders',
                'params' => [['a']],
                'outcome' => [
                    'seeders' => ['a'],
                ],
            ],
            'seeders 2' => [
                'method' => 'seeders',
                'params' => [[]],
                'outcome' => [
                    'seeders' => [],
                ],
            ],
            'noSeeders' => [
                'method' => 'noSeeders',
                'params' => [],
                'outcome' => [
                    'seeders' => [],
                ],
            ],

            'remoteBuildUrl 1' => [
                'method' => 'remoteBuildUrl',
                'params' => ['http://something'],
                'outcome' => [
                    'remoteBuildUrl' => 'http://something',
                ],
            ],
            'remoteBuildUrl 2' => [
                'method' => 'remoteBuildUrl',
                'params' => [null],
                'outcome' => [
                    'remoteBuildUrl' => null,
                ],
            ],
            'noRemoteBuildUrl' => [
                'method' => 'noRemoteBuildUrl',
                'params' => [],
                'outcome' => [
                    'remoteBuildUrl' => null,
                ],
            ],

            'cacheTools 1' => [
                'method' => 'cacheTools',
                'params' => [false, true, true],
                'outcome' => [
                    'reuseTransaction' => false,
                    'reuseJournal' => true,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [false, false, true],
                'outcome' => [
                    'reuseTransaction' => false,
                    'reuseJournal' => false,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [true, true, false],
                'outcome' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => true,
                    'scenarioTestDBs' => false,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [true, false, false],
                'outcome' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => false,
                    'scenarioTestDBs' => false,
                ],
            ],

            'reuseTransaction 1' => [
                'method' => 'reuseTransaction',
                'params' => [true],
                'outcome' => [
                    'reuseTransaction' => true,
                ],
            ],
            'reuseTransaction 2' => [
                'method' => 'reuseTransaction',
                'params' => [false],
                'outcome' => [
                    'reuseTransaction' => false,
                ],
            ],
            'noReuseTransaction' => [
                'method' => 'noReuseTransaction',
                'params' => [],
                'outcome' => [
                    'reuseTransaction' => false,
                ],
            ],

            'reuseJournal 1' => [
                'method' => 'reuseJournal',
                'params' => [true],
                'outcome' => [
                    'reuseJournal' => true,
                ],
            ],
            'reuseJournal 2' => [
                'method' => 'reuseJournal',
                'params' => [false],
                'outcome' => [
                    'reuseJournal' => false,
                ],
            ],
            'noReuseJournal' => [
                'method' => 'noReuseJournal',
                'params' => [],
                'outcome' => [
                    'reuseJournal' => false,
                ],
            ],

            'scenarioTestDBs 1' => [
                'method' => 'scenarioTestDBs',
                'params' => [true],
                'outcome' => [
                    'scenarioTestDBs' => true,
                ],
            ],
            'scenarioTestDBs 2' => [
                'method' => 'scenarioTestDBs',
                'params' => [false],
                'outcome' => [
                    'scenarioTestDBs' => false,
                ],
            ],
            'noScenarioTestDBs' => [
                'method' => 'noScenarioTestDBs',
                'params' => [],
                'outcome' => [
                    'scenarioTestDBs' => false,
                ],
            ],

            'snapshots 1' => [
                'method' => 'snapshots',
                'params' => ['afterMigrations', false],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => 'afterMigrations',
                    'useSnapshotsWhenNotReusingDB' => false,
                ],
            ],
            'snapshots 2' => [
                'method' => 'snapshots',
                'params' => ['afterSeeders', 'afterMigrations'],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => 'afterSeeders',
                    'useSnapshotsWhenNotReusingDB' => 'afterMigrations',
                ],
            ],
            'snapshots 3' => [
                'method' => 'snapshots',
                'params' => ['both', 'afterSeeders'],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => 'both',
                    'useSnapshotsWhenNotReusingDB' => 'afterSeeders',
                ],
            ],
            'snapshots 4' => [
                'method' => 'snapshots',
                'params' => [false, 'both'],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => false,
                    'useSnapshotsWhenNotReusingDB' => 'both',
                ],
            ],
            'noSnapshots' => [
                'method' => 'noSnapshots',
                'params' => [],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => false,
                    'useSnapshotsWhenNotReusingDB' => false,
                ],
            ],

            'forceRebuild 1' => [
                'method' => 'forceRebuild',
                'params' => [true],
                'outcome' => [
                    'forceRebuild' => true,
                ],
            ],
            'forceRebuild 2' => [
                'method' => 'forceRebuild',
                'params' => [false],
                'outcome' => [
                    'forceRebuild' => false,
                ],
            ],
            'dontForceRebuild' => [
                'method' => 'dontForceRebuild',
                'params' => [],
                'outcome' => [
                    'forceRebuild' => false,
                ],
            ],

            'isBrowserTest 1' => [
                'method' => 'isBrowserTest',
                'params' => [true],
                'outcome' => [
                    'isBrowserTest' => true,
                ],
            ],
            'isBrowserTest 2' => [
                'method' => 'isBrowserTest',
                'params' => [false],
                'outcome' => [
                    'isBrowserTest' => false,
                ],
            ],
            'isNotBrowserTest' => [
                'method' => 'isNotBrowserTest',
                'params' => [],
                'outcome' => [
                    'isBrowserTest' => false,
                ],
            ],
        ];
    }

    /**
     * Test that the HasConfigDTOTrait object can set and get values properly.
     *
     * @test
     * @dataProvider configDtoDataProvider
     * @param string  $method  The set method to call.
     * @param mixed[] $params  The parameters to pass to this set method, and the values to check after.
     * @param mixed[] $outcome The outcome values to check for (uses $params if not given).
     * @return void
     */
    public function has_config_dto_trait_can_set_and_get_values(
        string $method,
        array $params,
        array $outcome
    ) {

        $configDTO = new ConfigDTO();
        $object = new HasConfigDTOClass($configDTO);

        $callable = [$object, $method];
        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
        }

        foreach ($outcome as $field => $value) {
            $this->assertSame($value, $configDTO->$field);
        }
    }

    /**
     * Test that the HasConfigDTOTrait object can set and get values properly.
     *
     * @test
     * @return void
     */
    public function has_config_dto_trait_can_get_connection()
    {
        $configDTO = (new ConfigDTO())->connection('a');
        $object = new HasConfigDTOClass($configDTO);
        $this->assertSame('a', $object->getConnection());
    }
}
