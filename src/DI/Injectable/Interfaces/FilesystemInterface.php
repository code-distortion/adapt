<?php

namespace CodeDistortion\Adapt\DI\Injectable\Interfaces;

/**
 * Injectable class to abstract filesystem actions.
 */
interface FilesystemInterface
{
    /**
     * Check whether the given path exists.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function pathExists($path): bool;

    /**
     * Check whether the given path exists and is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function fileExists($path): bool;

    /**
     * Check whether the given path exists and is a directory.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function dirExists($path): bool;

    /**
     * Check whether the given path is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function isFile($path): bool;

    /**
     * Touch the given file.
     *
     * @param string $path The path to touch.
     * @return boolean
     */
    public function touch($path): bool;

    /**
     * Returns canonicalized absolute pathname.
     *
     * @param string $path The path being checked.
     * @return string|null
     */
    public function realpath($path);

    /**
     * Remove the current path prefix from the given path.
     *
     * @param string      $path     The path to alter.
     * @param string|null $basePath The base-path prefix to remove.
     * @return string
     */
    public function removeBasePath($path, $basePath = null): string;

    /**
     * Write to the given file.
     *
     * @param string $path    The path of the file to write to.
     * @param string $mode    The write-mode.
     * @param string $content The content to write.
     * @return boolean
     */
    public function writeFile($path, $mode, $content): bool;

    /**
     * Copy the given file to another location.
     *
     * @param string $srcPath  The path to copy.
     * @param string $destPath The destination location.
     * @return boolean
     */
    public function copy($srcPath, $destPath): bool;

    /**
     * Rename a file.
     *
     * @param string $srcPath  The path to the file to rename.
     * @param string $destPath The destination name.
     * @return boolean
     */
    public function rename($srcPath, $destPath): bool;

    /**
     * Delete the given file.
     *
     * @param string $path The path to delete.
     * @return boolean
     */
    public function unlink($path): bool;

    /**
     * Create a directory.
     *
     * @param string  $pathname  The directory path.
     * @param integer $mode      The mode to set the directory to.
     * @param boolean $recursive Allows the creation of nested directories specified in the pathname.
     * @return boolean
     */
    public function mkdir($pathname, $mode = 0777, $recursive = false): bool;

    /**
     * Generate an md5 of the given file.
     *
     * @param string $path The path of the file to hash.
     * @return string|null
     */
    public function md5File($path);

    /**
     * Get the size of the file in bytes.
     *
     * @param string $path The path of the file to get the size of.
     * @return integer|null
     */
    public function size($path);

    /**
     * Return the files in a directory.
     *
     * @param string  $dir       The directory to look in.
     * @param boolean $recursive Look for files recursively?.
     * @return string[]
     */
    public function filesInDir($dir, $recursive = false): array;
}
