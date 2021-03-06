<?php

namespace CodeDistortion\Adapt\Tests\Integration\Laravel;

use Artisan;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Settings;
use CodeDistortion\Adapt\Support\StringSupport;
use CodeDistortion\Adapt\Tests\Integration\Support\AssignClassAlias;
use CodeDistortion\Adapt\Tests\Integration\Support\DatabaseBuilderTestTrait;
use CodeDistortion\Adapt\Tests\LaravelTestCase;

AssignClassAlias::databaseBuilderSetUpTrait(__NAMESPACE__);

/**
 * Test the DatabaseBuilder class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CommandsTest extends LaravelTestCase
{
    use DatabaseBuilderSetUpTrait; // this is chosen above by AssignClassAlias depending on the version of Laravel used
    use DatabaseBuilderTestTrait;


    /**
     * Provide data for the
     * test_list_db_caches_command test.
     *
     * @return mixed[][]
     */
    public function listDBCachesDataProvider(): array
    {
        return [
            [
                'config' => $this->newConfigDTO('sqlite'),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(false, false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-a34cd538e35f.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-8bd51f9f0b21.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]\n"
                    . "- [file3]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- [file2]\n"
                    . "- [file3]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-8bd51f9f0b21.sqlite',
                    '[file3]' => '[adapt-test-storage]/snapshot.database.338349-a34cd538e35f.sqlite',
                ],
            ],
        ];
    }

    /**
     * Test the adapt:list-db-caches command
     *
     * @test
     * @dataProvider listDBCachesDataProvider
     * @param ConfigDTO $config                        The ConfigDTO to use which instructs what and how to build.
     * @param string    $expectedOutput                The expected adapt:list-db-caches output.
     * @param string    $expectedOutputWithTestingConn The expected adapt:list-db-caches output when the "testing" db
     *                                                 connection is present.
     * @param string[]  $substitutions                 File substitutions to replace after resolving their paths and
     *                                                 size.
     * @return void
     */
    public function test_list_db_caches_command(
        ConfigDTO $config,
        string $expectedOutput,
        string $expectedOutputWithTestingConn,
        array $substitutions
    ) {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->expectCommandOutput('adapt:list-db-caches', [], 'There are no caches.');

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->newDatabaseBuilder($config)->execute();

        // Laravel <= 5.1 doesn't have the "testing" connection so the output is different
        $hasTestingConnection = (config('database.connections.testing') !== null);
        $expectedOutput = ($hasTestingConnection ? $expectedOutputWithTestingConn : $expectedOutput);
        $expectedOutput = $this->resolveExpectedOutput($expectedOutput, $substitutions);

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->expectCommandOutput('adapt:list-db-caches', [], $expectedOutput);
    }


    /**
     * Provide data for the test_remove_db_caches_command test.
     *
     * @return mixed[][]
     */
    public function removeDBCachesDataProvider(): array
    {
        return [
            [
                'config' => $this->newConfigDTO('sqlite'),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - DELETED [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots(false, false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - DELETED [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterMigrations', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - DELETED [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-a34cd538e35f.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('afterSeeders', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - DELETED [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-8bd51f9f0b21.sqlite',
                ],
            ],
            [
                'config' => $this->newConfigDTO('sqlite')
                    ->snapshots('both', false),
                'expectedOutput' =>
                    "Test-databases:\n\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]\n"
                    . "- DELETED [file3]",
                'expectedOutputWithTestingConn' =>
                    "Test-databases:\n\n"
                    . "- Connection \"testing\":\n"
                    . "  - DELETED [file1]\n"
                    . "- Connection \"sqlite\":\n"
                    . "  - DELETED [file1]\n\n"
                    . "Snapshots:\n\n"
                    . "- DELETED [file2]\n"
                    . "- DELETED [file3]",
                'substitutions' => [
                    '[file1]' => '[adapt-test-storage]/test-database.338349-e2dee1963369.sqlite',
                    '[file2]' => '[adapt-test-storage]/snapshot.database.338349-8bd51f9f0b21.sqlite',
                    '[file3]' => '[adapt-test-storage]/snapshot.database.338349-a34cd538e35f.sqlite',
                ],
            ],
        ];
    }

    /**
     * Test the adapt:remove-db-caches command
     *
     * @test
     * @dataProvider removeDBCachesDataProvider
     * @param ConfigDTO $config                        The ConfigDTO to use which instructs what and how to build.
     * @param string    $expectedOutput                The expected adapt:list-db-caches output.
     * @param string    $expectedOutputWithTestingConn The expected adapt:list-db-caches output when the "testing" db
     *                                                 connection is present.
     * @param string[]  $substitutions                 File substitutions to replace after resolving their paths and
     *                                                 size.
     * @return void
     */
    public function test_remove_db_caches_command(
        ConfigDTO $config,
        string $expectedOutput,
        string $expectedOutputWithTestingConn,
        array $substitutions
    ) {

        $this->prepareWorkspace("$this->workspaceBaseDir/scenario1", $this->wsCurrentDir);

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->expectCommandOutput('adapt:remove-db-caches', ['--force' => true], 'There are no caches to remove.');

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->newDatabaseBuilder($config)->execute();

        // Laravel <= 5.1 doesn't have the "testing" connection so the output is different
        $hasTestingConnection = (config('database.connections.testing') !== null);
        $expectedOutput = ($hasTestingConnection ? $expectedOutputWithTestingConn : $expectedOutput);
        $expectedOutput = $this->resolveExpectedOutput($expectedOutput, $substitutions);

        Settings::resetStaticProps();
        $this->useConfig($config);
        $this->expectCommandOutput('adapt:remove-db-caches', ['--force' => true], $expectedOutput);
    }


    /**
     * Run the given command and check the output.
     *
     * @param string   $expectedOutput The output to expect.
     * @param string[] $substitutions  File substitutions to replace after resolving their paths and size.
     * @return string
     */
    private function resolveExpectedOutput(string $expectedOutput, array $substitutions): string
    {
        $replacements = [
            '[adapt-test-storage]' => config(Settings::LARAVEL_CONFIG_NAME . '.storage_dir'),
        ];

        foreach ($substitutions as $key => $file) {
            $file = str_replace(array_keys($replacements), $replacements, $file);
            $size = (file_exists($file)
                ? StringSupport::readableSize((int) filesize($file))
                : 0);
            $substitutions[$key] = "$file $size";
        }

        return str_replace(array_keys($substitutions), $substitutions, $expectedOutput);
    }

    /**
     * Run the given command and check the output.
     *
     * @param string  $command        The command to run.
     * @param mixed[] $args           The arguments to pass to the command.
     * @param string  $expectedOutput The output to expect.
     * @return void
     */
    private function expectCommandOutput(string $command, array $args, string $expectedOutput)
    {
//        Laravel >= 5.4 lets you pass BufferedOutput to collect the output
//        $outputBuffer = new BufferedOutput();
//        Artisan::call($command, $args, $outputBuffer);
//        $this->assertSame($expectedOutput, trim($outputBuffer->fetch()));

        // this is compatible with Laravel < 5.4
        Artisan::call($command, $args);
        $this->assertSame($expectedOutput, trim(Artisan::output()));
    }
}
