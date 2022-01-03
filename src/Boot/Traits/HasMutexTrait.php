<?php

namespace CodeDistortion\Adapt\Boot\Traits;

/**
 * Let a class create a mutex.
 */
trait HasMutexTrait
{
    /** @var resource|null The file resource containing the lock. */
    private $lockFP = null;

    /** @var string|null The path to the lock file. */
    private $lockPath;



    /**
     * Obtain a mutex-lock.
     *
     * @param string $path The path of the file to use as the locking mechanism.
     * @return boolean
     */
    public function getMutexLock($path): bool
    {
        if ($this->lockFP) {
            return false;
        }

        $fp = fopen($path, 'w+');
        if (!$fp) {
            return false;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }
        $this->lockPath = $path;
        $this->lockFP = $fp;

        return true;
    }



    /**
     * Release a mutex-lock.
     *
     * @return boolean
     */
    public function releaseMutexLock(): bool
    {
        if (!$this->lockFP) {
            return false;
        }

        flock($this->lockFP, LOCK_UN);
        fclose($this->lockFP);

        $this->lockFP = $this->lockPath = null;

        return true;
    }
}
