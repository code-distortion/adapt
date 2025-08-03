<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use CodeDistortion\Adapt\Tests\Unit\DTO\Support\HasConfigDTOClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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
    public static function configDtoDataProvider(): array
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

            'cacheInvalidationMethod 1' => [
                'method' => 'cacheInvalidationMethod',
                'params' => ['content'],
                'outcome' => [
                    'cacheInvalidationMethod' => 'content',
                ],
            ],
            'cacheInvalidationMethod 2' => [
                'method' => 'cacheInvalidationMethod',
                'params' => ['modified'],
                'outcome' => [
                    'cacheInvalidationMethod' => 'modified',
                ],
            ],

            'initialImports 1' => [
                'method' => 'initialImports',
                'params' => [['a']],
                'outcome' => [
                    'initialImports' => ['a'],
                ],
            ],
            'initialImports 2' => [
                'method' => 'initialImports',
                'params' => [[]],
                'outcome' => [
                    'initialImports' => [],
                ],
            ],
            'noInitialImports' => [
                'method' => 'noInitialImports',
                'params' => [],
                'outcome' => [
                    'initialImports' => [],
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
                    'scenarios' => true,
                ],
            ],
            'cacheTools 2' => [
                'method' => 'cacheTools',
                'params' => [false, false, true],
                'outcome' => [
                    'reuseTransaction' => false,
                    'reuseJournal' => false,
                    'scenarios' => true,
                ],
            ],
            'cacheTools 3' => [
                'method' => 'cacheTools',
                'params' => [true, true, false],
                'outcome' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => true,
                    'scenarios' => false,
                ],
            ],
            'cacheTools 4' => [
                'method' => 'cacheTools',
                'params' => [true, false, false],
                'outcome' => [
                    'reuseTransaction' => true,
                    'reuseJournal' => false,
                    'scenarios' => false,
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

            'scenarios 1' => [
                'method' => 'scenarios',
                'params' => [true],
                'outcome' => [
                    'scenarios' => true,
                ],
            ],
            'scenarios 2' => [
                'method' => 'scenarios',
                'params' => [false],
                'outcome' => [
                    'scenarios' => false,
                ],
            ],
            'noScenarios' => [
                'method' => 'noScenarios',
                'params' => [],
                'outcome' => [
                    'scenarios' => false,
                ],
            ],

            'snapshots 1' => [
                'method' => 'snapshots',
                'params' => ['afterMigrations'],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => 'afterMigrations',
                ],
            ],
            'noSnapshots' => [
                'method' => 'noSnapshots',
                'params' => [],
                'outcome' => [
                    'useSnapshotsWhenReusingDB' => null,
                    'useSnapshotsWhenNotReusingDB' => null,
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
     *
     * @dataProvider configDtoDataProvider
     * @param string  $method  The set method to call.
     * @param mixed[] $params  The parameters to pass to this set method, and the values to check after.
     * @param mixed[] $outcome The outcome values to check for (uses $params if not given).
     * @return void
     */
    #[Test]
    #[DataProvider('configDtoDataProvider')]
    public static function has_config_dto_trait_can_set_and_get_values(
        string $method,
        array $params,
        array $outcome
    ) {

        $configDTO = new ConfigDTO();
        $object = new HasConfigDTOClass($configDTO);

        $callable = [$object, $method];
//        if (is_callable($callable)) {
            call_user_func_array($callable, $params);
//        }

        foreach ($outcome as $field => $value) {
            self::assertSame($value, $configDTO->$field);
        }
    }

    /**
     * Test that the HasConfigDTOTrait object can set and get values properly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public static function has_config_dto_trait_can_get_connection()
    {
        $configDTO = (new ConfigDTO())->connection('a');
        $object = new HasConfigDTOClass($configDTO);
        self::assertSame('a', $object->getConnection());
    }
}
