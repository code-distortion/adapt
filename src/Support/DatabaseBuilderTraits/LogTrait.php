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
        $prepLine = "Preparing the \"{$this->config->connection}\" database";
        if ($this->config->shouldBuildRemotely()) {
            $prepLine .= " remotely";
        } elseif ($this->config->isRemoteBuild) {
            $prepLine .= " locally, for another Adapt installation";
        }

        $this->di->log->logBox(
            [$prepLine, "For test \"{$this->config->testName}\""],
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
        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderBuildSettings());
        $this->di->log->logBox($lines, 'Build Settings');

        $lines = $this->di->log->padList($this->resolvedSettingsDTO->renderResolvedDatabaseSettings());
        $this->di->log->logBox($lines, 'Resolved Database');

//        $this->di->log->debug('Build Settings:');
//        foreach ($lines as $line) {
//            $this->di->log->debug($line);
//        }
    }
}
