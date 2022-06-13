<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Support\Settings;

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
    private function logTitle()
    {
        $prepLine = "Preparing a database for connection \"{$this->configDTO->connection}\"";
        if ($this->configDTO->shouldBuildRemotely()) {
            $prepLine .= " remotely";
        } elseif ($this->configDTO->isRemoteBuild) {
            $prepLine .= " locally, for another Adapt installation";
        }

        $this->di->log->logBox([$prepLine, "For test \"{$this->configDTO->testName}\""], 'ADAPT (v' . Settings::PACKAGE_VERSION . ')', 'vDebug');
    }

    /**
     * Log the details about the settings being used.
     *
     * @return void
     */
    private function logTheUsedSettings()
    {
        if (!$this->resolvedSettingsDTO) {
            return;
        }

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderBuildSources());
        $this->di->log->logBox($lines, 'Build Sources', 'vDebug');

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderBuildEnvironmentSettings());
        $this->di->log->logBox($lines, 'Build Environment', 'vDebug');

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderResolvedDatabaseSettings());
        $this->di->log->logBox($lines, 'Resolved Database', 'vDebug');
    }

    /**
     * Log the fact that the remotely-built database is being reused before sending the http request.
     *
     * @param string  $database The database being reused.
     * @param integer $logTimer The timer, started a little earlier.
     * @return void
     */
    private function logHttpRequestWasSaved(string $database, int $logTimer)
    {
        $this->di->log->vDebug(
            "Database \"$database\" was already prepared remotely, and can be reused without sending another request",
            $logTimer
        );
    }
}
