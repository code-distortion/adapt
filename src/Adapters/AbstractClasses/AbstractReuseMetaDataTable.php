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



    /**
     * Load the reuse details from the meta-data table.
     *
     * @return stdClass|null
     */
    abstract protected function loadReuseInfo();

    /**
     * Check to see if the database can be reused.
     *
     * @param string|null $buildHash    The current build-hash.
     * @param string|null $scenarioHash The current scenario-hash.
     * @param string|null $projectName  The project-name.
     * @param string      $database     The database being built.
     * @return boolean
     * @throws AdaptBuildException When the database is owned by another project.
     */
    public function dbIsCleanForReuse(
        $buildHash,
        $scenarioHash,
        $projectName,
        $database
    ): bool {

        try {
            $reuseInfo = $this->loadReuseInfo();
        } catch (Throwable $e) {
            return false;
        }

        if (!$reuseInfo) {
            return false;
        }

        if ($reuseInfo->project_name !== $projectName) {
            throw AdaptBuildException::databaseOwnedByAnotherProject($database, $reuseInfo->project_name);
        }

        if ($reuseInfo->reuse_table_version != Settings::REUSE_TABLE_VERSION) {
            return false;
        }

        if ($reuseInfo->build_hash !== $buildHash) {
            return false;
        }

        if ($reuseInfo->scenario_hash !== $scenarioHash) {
            return false;
        }

        if (($reuseInfo->transaction_reusable === 0) || ($reuseInfo->transaction_reusable === false)) {
//            $this->di->log->warning(
//                'The previous transaction for database "' . $database . '" '
//                . 'was committed instead of being rolled-back'
//            );
            return false;
        }

        if (($reuseInfo->journal_reusable === 0) || ($reuseInfo->journal_reusable === false)) {
            return false;
        }

        if (($reuseInfo->validation_passed === 0) || ($reuseInfo->validation_passed === false)) {
            return false;
        }

        if (!$reuseInfo->transaction_reusable && !$reuseInfo->journal_reusable) {
            return false;
        }

        return true;
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
