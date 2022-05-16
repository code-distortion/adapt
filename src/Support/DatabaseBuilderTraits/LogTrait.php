<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;

/**
 * @mixin DatabaseBuilder
 */
trait LogTrait
{
    /**
     * Log the title line.
     *
     * @return void
     */
    private function logTitle(): void
    {
        $prepLine = "Preparing the database for connection \"{$this->configDTO->connection}\"";
        if ($this->configDTO->shouldBuildRemotely()) {
            $prepLine .= " remotely";
        } elseif ($this->configDTO->isRemoteBuild) {
            $prepLine .= " locally, for another Adapt installation";
        }

        $this->di->log->logBox(
            [$prepLine, "For test \"{$this->configDTO->testName}\""],
            'ADAPT - Preparing a Test-Database'
        );
    }

    /**
     * Log the details about the settings being used.
     *
     * @return void
     */
    private function logTheUsedSettings(): void
    {
        if (!$this->resolvedSettingsDTO) {
            return;
        }

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderBuildSources());
        $this->di->log->logBox($lines, 'Build Sources');

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderBuildEnvironmentSettings());
        $this->di->log->logBox($lines, 'Build Environment');

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderResolvedDatabaseSettings());
        $this->di->log->logBox($lines, 'Resolved Database');
    }

    /**
     * Log the fact that the remotely-built database is being reused before sending the http request.
     *
     * @param string  $database The database being reused.
     * @param integer $logTimer The timer, started a little earlier.
     * @return void
     */
    private function logHttpRequestWasSaved(string $database, int $logTimer): void
    {
        $this->di->log->debug(
            "Database \"$database\" was already prepared remotely, and can be reused without sending another request",
            $logTimer
        );
    }
}
