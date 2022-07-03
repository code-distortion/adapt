<?php

namespace CodeDistortion\Adapt\Adapters\AbstractClasses;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseMetaDataTableInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\Settings;
use DateTime;
use DateTimeZone;
use stdClass;
use Throwable;

/**
 * Database-adapter methods related to managing reuse meta-data.
 */
abstract class AbstractReuseMetaDataTable implements ReuseMetaDataTableInterface
{
    use InjectTrait;



    /** @var string|null The reason why the database can't be reused. */
    private $cantReuseReason;



    /**
     * Load the reuse details from the meta-data table.
     *
     * @return stdClass|null
     */
    abstract protected function loadReuseInfo();

    /**
     * Check to see if the database can be reused.
     *
     * @param string|null $buildChecksum    The current build-checksum.
     * @param string|null $scenarioChecksum The current scenario-checksum.
     * @param string|null $projectName      The project-name.
     * @param string      $database         The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        $buildChecksum,
        $scenarioChecksum,
        $projectName,
        $database
    ): bool {

        $this->cantReuseReason = null;

        try {
            $reuseInfo = $this->loadReuseInfo();
        } catch (Throwable $e) {
            $this->cantReuseReason = "An exception occurred when reading the re-use info - \"{$e->getMessage()}\"";
            return false;
        }

        if (!$reuseInfo) {
            $this->cantReuseReason = "the re-use info row doesn't exist";
            return false;
        }

        if ($reuseInfo->project_name !== $projectName) {
            $this->cantReuseReason = "the database is owned by another project "
                . "{$this->renderComparison($reuseInfo->project_name, $projectName)}";
            throw AdaptBuildException::databaseOwnedByAnotherProject($database, $reuseInfo->project_name);
        }

        if ($reuseInfo->reuse_table_version != Settings::REUSE_TABLE_VERSION) {
            $this->cantReuseReason = "the reuse version doesn't match";
            return false;
        }

        if ($reuseInfo->build_checksum !== $buildChecksum) {
            $this->cantReuseReason = "the build-checksum doesn't match "
                . "{$this->renderComparison($reuseInfo->build_checksum, $buildChecksum)}";
            return false;
        }

        if ($reuseInfo->scenario_checksum !== $scenarioChecksum) {
            $this->cantReuseReason = "the scenario-checksum doesn't match "
                . "{$this->renderComparison($reuseInfo->scenario_checksum, $scenarioChecksum)}";
            return false;
        }

        if (($reuseInfo->transaction_reusable === 0) || ($reuseInfo->transaction_reusable === false)) {
            $this->cantReuseReason = "the wrapper-transaction was committed or rolled-back";
            return false;
        }

        if (($reuseInfo->journal_reusable === 0) || ($reuseInfo->journal_reusable === false)) {
            $this->cantReuseReason = "the rewind journaling failed";
            return false;
        }

        if (($reuseInfo->validation_passed === 0) || ($reuseInfo->validation_passed === false)) {
            $this->cantReuseReason = "the post-test database validation failed";
            return false;
        }

        if (!$reuseInfo->transaction_reusable && !$reuseInfo->journal_reusable) {
            $this->cantReuseReason = "no re-use mechanism was used";
            return false;
        }

        return true;
    }

    /**
     * Get the reason why the database couldn't be reused.
     *
     * @return string|null
     */
    public function getCantReuseReason()
    {
        return $this->cantReuseReason;
    }

    /**
     * Render the comparison of two database values.
     *
     * @param string|null $value1 The first value to compare.
     * @param string|null $value2 The second value to compare.
     * @return string
     */
    private function renderComparison($value1, $value2): string
    {
        return "({$this->renderDbValue($value1)} != {$this->renderDbValue($value2)})";
    }

    /**
     * Escape a database value, for reading.
     *
     * @param string|null $value The value to escape.
     * @return string
     */
    private function renderDbValue($value): string
    {
        return !is_null($value) ? "\"$value\"" : 'null';
    }

    /**
     * Render the current time in UTC as a string.
     *
     * @return string
     */
    protected function nowUtcString(): string
    {
        return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
