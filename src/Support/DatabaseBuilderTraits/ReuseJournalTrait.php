<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;

/**
 * @mixin DatabaseBuilder
 */
trait ReuseJournalTrait
{
    /**
     * Create journal tables and triggers to populate them, for each table.
     *
     * @return void
     */
    private function setUpJournaling(): void
    {
        if (!$this->configDTO->shouldUseJournal()) {
            return;
        }

        $this->dbAdapter()->reuseJournal->setUpJournal();
    }

    /**
     * Record that journaling has begun.
     *
     * This is run automatically if setUpJournaling() above is called, but can be called directly here (when the
     * database was built remotely) so the journaling tables aren't set-up again.
     *
     * @return void
     */
    public function recordJournalingStart(): void
    {
        if (!$this->configDTO->shouldUseJournal()) {
            return;
        }

        $this->dbAdapter()->reuseJournal->recordJournalingStart();
    }

    /**
     * Take the journal information and "undo" the changes.
     *
     * @param boolean $addNewLineAfter Whether a new line should be added after logging or not.
     * @return void
     */
    private function reverseJournal(bool $addNewLineAfter): void
    {
        if (!$this->configDTO->shouldUseJournal()) {
            return;
        }

        $this->dbAdapter()->reuseJournal->reverseJournal($addNewLineAfter);
    }
}
