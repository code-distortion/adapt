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
     * Provide data for the config_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function configDtoDataProvider(): array
    {
        return [
            'databaseModifier' => [
                'method' => 'databaseModifier',
                'params' => ['1'],
                'outcome' => [
                    'databaseModifier' => '1',
                ],
            ],
            'noDatabaseModifier' => [
                'method' => 'noDatabaseModifier',
                'params' => [],
                'outcome' => [
                    'databaseModifier' => '',
                ],
            ],

            'preMigrationImports' => [
                'method' => 'preMigrationImports',
                'params' => [['a']],
                'outcome' => [
                    'preMigrationImports' => ['a'],
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

            'seeders' => [
                'method' => 'seeders',
                'params' => [['a']],
                'outcome' => [
                    'seeders' => ['a'],
                ],
            ],
            'noSeeders' => [
                'method' => 'noSeeders',
                'params' => [],
                'outcome' => [
                    'seeders' => [],
                ],
            ],

            'cacheTools 1' => [
                'method' => 'cacheTools',
                'params' => [true, false],
                'outcome' => [
                    'reuseTestDBs' => true,
                    'scenarioTestDBs' => false,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [false, true],
                'outcome' => [
                    'reuseTestDBs' => false,
                    'scenarioTestDBs' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [false, false],
                'outcome' => [
                    'reuseTestDBs' => false,
                    'scenarioTestDBs' => false,
                ],
            ],

            'reuseTestDBs 1' => [
                'method' => 'reuseTestDBs',
                'params' => [true],
                'outcome' => [
                    'reuseTestDBs' => true,
                ],
            ],
            'reuseTestDBs 2' => [
                'method' => 'reuseTestDBs',
                'params' => [false],
                'outcome' => [
                    'reuseTestDBs' => false,
                ],
            ],
            'noReuseTestDBs' => [
                'method' => 'noReuseTestDBs',
                'params' => [],
                'outcome' => [
                    'reuseTestDBs' => false,
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

        $config = new ConfigDTO();
        $object = new HasConfigDTOClass($config);

        $callable = [$object, $method];
        is_callable($callable) ? call_user_func_array($callable, $params) : null;

        foreach ($outcome as $field => $value) {
            $this->assertSame($value, $config->$field);
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
        $config = (new ConfigDTO())->connection('a');
        $object = new HasConfigDTOClass($config);
        $this->assertSame('a', $object->getConnection());
    }
}
