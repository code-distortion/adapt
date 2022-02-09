<?php

namespace CodeDistortion\Adapt\PreBoot;

use CodeDistortion\Adapt\Boot\BootTestInterface;
use CodeDistortion\Adapt\Boot\BootTestLaravel;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Pre-Bootstrap for Laravel tests.
 *
 * Used so Laravel specific pre-booting code doesn't need to exist in the InitialiseLaravelAdapt trait.
 */
class PreBootTestLaravel
{
    /** @var BootTestInterface The object used to boot Adapt. */
    public BootTestInterface $adaptBootTestLaravel;

    /** @var string The name of the current test. */
    private string $testName;

    /** @var PropBagDTO The properties specified in the test-class. */
    private PropBagDTO $propBag;

    /** @var callable The framework specific callback to set up the database transaction. */
    private $buildTransactionClosure;

    /** @var callable The callback that uses databaseInit(), to let the Test customise the database build process. */
    private $buildInitCallback;

    /** @var boolean Whether the current test is a browser test or not. */
    private bool $isBrowserTest;



    /**
     * Constructor.
     *
     * @param string        $testName                The name of the current test.
     * @param PropBagDTO    $propBag                 The properties specified in the test-class.
     * @param callable      $buildTransactionClosure The closure to call to set up the database transaction.
     * @param callable|null $buildInitCallback       The callback that calls the custom databaseInit() build process.
     * @param boolean       $isBrowserTest           Whether the current test is a browser test or not.
     */
    public function __construct(
        string $testName,
        PropBagDTO $propBag,
        callable $buildTransactionClosure,
        ?callable $buildInitCallback,
        bool $isBrowserTest
    ) {
        $this->testName = $testName;
        $this->propBag = $propBag;
        $this->buildTransactionClosure = $buildTransactionClosure;
        $this->buildInitCallback = $buildInitCallback;
        $this->isBrowserTest = $isBrowserTest;
    }



    /**
     * Prepare and boot Adapt.
     *
     * @return void
     */
    public function adaptSetUp(): void
    {
        $this->adaptPrepareLaravelConfig();

        $this->adaptBootTestLaravel = $this->adaptBuildBootObject();
        $this->adaptBootTestLaravel->run();
    }

    /**
     * Perform any clean-up / checking once the test has finished.
     *
     * @return void
     */
    public function adaptTearDown(): void
    {
        try {
            $this->adaptBootTestLaravel->checkForCommittedTransactions();
        } finally {
            $this->adaptBootTestLaravel->postTestCleanUp();
        }
    }



    /**
     * Update the Laravel config ready for the tests to run.
     *
     * @return void
     */
    private function adaptPrepareLaravelConfig(): void
    {
        $this->adaptInitLaravelDefaultConnection();
        $this->adaptRemapLaravelDBConnections();
    }

    /**
     * Choose the database connection to use for this test, and set it as Laravel's default database connection.
     *
     * @return void
     * @throws AdaptConfigException Thrown when the desired default connection doesn't exist.
     */
    private function adaptInitLaravelDefaultConnection(): void
    {
        if (!$this->propBag->hasProp('defaultConnection')) {
            return;
        }

        $connection = $this->propBag->prop('defaultConnection');
        if (!config("database.connections.$connection")) {
            throw AdaptConfigException::invalidDefaultConnection($connection);
        }

        config(['database.default' => $connection]);
    }

    /**
     * Remap the config database connections, overwriting ones with others.
     *
     * @return void
     */
    private function adaptRemapLaravelDBConnections(): void
    {
        foreach ($this->adaptParseRemapDBStrings() as $dest => $src) {
            $replacement = config("database.connections.$src");
            config(["database.connections.$dest" => $replacement]);
        }
    }

    /**
     * Break down the remap-database strings and pick out the important ones.
     *
     * Gives priority the ones specified as props, but higher than that it gives priority to ones that start with "!".
     *
     * @return array
     */
    private function adaptParseRemapDBStrings(): array
    {
        return array_merge(
            $this->adaptParseRemapDBString($this->propBag->config('remap_connections'), null, true),
            $this->adaptParseRemapDBString($this->propBag->prop('remapConnections', ''), null, false),
            $this->adaptParseRemapDBString($this->propBag->config('remap_connections'), true, true),
            $this->adaptParseRemapDBString($this->propBag->prop('remapConnections', ''), true, false)
        );
    }

    /**
     * Break down the given remap-database string into its parts.
     *
     * @param string|null  $remapString  The string to use.
     * @param boolean|null $getImportant Return "important" or "unimportant" ones? null for any.
     * @param boolean      $isConfig     Is this string from a config setting? (otherwise it's a test-class prop).
     * @return array
     * @throws AdaptConfigException Thrown when the string can't be interpreted.
     */
    private function adaptParseRemapDBString(?string $remapString, ?bool $getImportant, bool $isConfig): array
    {
        if (is_null($remapString)) {
            return [];
        }

        $remap = [];
        foreach (explode(',', $remapString) as $mapping) {

            $orig = $mapping;
            $mapping = str_replace(' ', '', $mapping);
            if (!mb_strlen($mapping)) {
                continue;
            }

            if (preg_match('/(!?)([^<]+)<(.+)/', $mapping, $matches)) {

                $isImportant = (bool) $matches[1];
                if ((is_null($getImportant)) || ($getImportant === $isImportant)) {

                    $dest = $matches[2];
                    $src = $matches[3];

                    if (!config("database.connections.$dest")) {
                        throw AdaptConfigException::missingDestRemapConnection($dest, $isConfig);
                    }
                    if (!config("database.connections.$src")) {
                        throw AdaptConfigException::missingSrcRemapConnection($src, $isConfig);
                    }

                    $remap[$dest] = $src;
                }
            } else {
                throw AdaptConfigException::invalidConnectionRemapString($orig, $isConfig);
            }
        }
        return $remap;
    }



    /**
     * Build the boot-test object.
     *
     * @return BootTestInterface
     */
    private function adaptBuildBootObject(): BootTestInterface
    {
        return (new BootTestLaravel())
            ->testName(get_class($this) . '::' . $this->testName)
            ->props($this->propBag)
            ->browserTestDetected($this->isBrowserTest)
            ->transactionClosure($this->buildTransactionClosure)
            ->initCallback($this->buildInitCallback)
            ->ensureStorageDirExists();
    }
}