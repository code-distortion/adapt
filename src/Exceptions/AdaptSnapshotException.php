<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when dealing with snapshot files.
 */
class AdaptSnapshotException extends AdaptException
{
    /**
     * Could not remove snapshot files.
     *
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function couldNotRemoveSnapshots(Throwable $originalException): self
    {
        return new self('Had trouble removing snapshot files', 0, $originalException);
    }
    /**
     * Could not find snapshot files.
     *
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function hadTroubleFindingSnapshots(Throwable $originalException): self
    {
        return new self('Had trouble finding snapshot files', 0, $originalException);
    }

    /**
     * Could not import a snapshot file.
     *
     * @param string $path The path of the file being imported.
     * @return self
     */
    public static function importFailed(string $path): self
    {
        return new self('The import of "'.$path.'" failed');
    }

    /**
     * The MySQL client isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqlClientNotPresent(string $path): self
    {
        return new self(
            'The mysql client "'.$path.'" executable isn\'t available to use '
            .'(check that it has been installed and that the path is correct)'
        );
    }

    /**
     * The mysqldump executable isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqldumpNotPresent(string $path): self
    {
        return new self(
            'The mysqldump executable "'.$path.'" isn\'t available to use'
            .'(check that it has been installed and that the path is correct)'
        );
    }

    /**
     * The MySQL client gave an error while importing.
     *
     * @param string  $path      The file being imported from.
     * @param integer $returnVal The error code given by mysql.
     * @return self
     */
    public static function mysqlImportError(string $path, int $returnVal): self
    {
        return new self('Could not import database from "'.$path.'" - the mysql return value was: '.$returnVal);
    }

    /**
     * The MySQL client gave an error while exporting.
     *
     * @param string  $path      The file being imported from.
     * @param integer $returnVal The error code given by mysql.
     * @return self
     */
    public static function mysqlExportError(string $path, int $returnVal): self
    {
        return new self('Could not export database to "'.$path.'" - the mysqldump return value was: '.$returnVal);
    }
}
