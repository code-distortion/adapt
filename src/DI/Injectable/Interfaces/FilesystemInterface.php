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
    public function pathExists(string $path): bool;

    /**
     * Check whether the given path exists and is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function fileExists(string $path): bool;

    /**
     * Check whether the given path exists and is a directory.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function dirExists(string $path): bool;

    /**
     * Check whether the given path is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function isFile(string $path): bool;

    /**
     * Check whether the given path is a directory.
     *
     * @param string $path The path to check.
     * @return boolean
     */
//    public function isDir(string $path): bool;

    /**
     * Touch the given file.
     *
     * @param string $path The path to touch.
     * @return boolean
     */
    public function touch(string $path): bool;

    /**
     * Returns canonicalized absolute pathname.
     *
     * @param string $path The path being checked.
     * @return string|null
     */
    public function realpath(string $path);

    /**
     * Remove the current path prefix from the given path.
     *
     * @param string      $path     The path to alter.
     * @param string|null $basePath The base-path prefix to remove.
     * @return string
     */
    public function removeBasePath(string $path, $basePath = null): string;

    /**
     * Write to the given file.
     *
     * @param string $path    The path of the file to write to.
     * @param string $mode    The write-mode.
     * @param mixed  $content The content to write.
     * @return boolean
     */
    public function writeFile(string $path, string $mode, $content): bool;

    /**
     * Copy the given file to another location.
     *
     * @param string $srcPath  The path to copy.
     * @param string $destPath The destination location.
     * @return boolean
     */
    public function copy(string $srcPath, string $destPath): bool;

    /**
     * Rename a file.
     *
     * @param string $srcPath  The path to the file to rename.
     * @param string $destPath The destination name.
     * @return boolean
     */
    public function rename(string $srcPath, string $destPath): bool;

    /**
     * Delete the given file.
     *
     * @param string $path The path to delete.
     * @return boolean
     */
    public function unlink(string $path): bool;

    /**
     * Create a directory.
     *
     * @param string  $pathname  The directory path.
     * @param integer $mode      The mode to set the directory to.
     * @param boolean $recursive Allows the creation of nested directories specified in the pathname.
     * @return boolean
     */
    public function mkdir(string $pathname, int $mode = 0777, bool $recursive = false): bool;

    /**
     * Generate an md5 of the given file.
     *
     * @param string $path The path of the file to hash.
     * @return string|null
     */
    public function md5File(string $path);

    /**
     * Get the size of the file in bytes.
     *
     * @param string $path The path of the file to get the size of.
     * @return integer|null
     */
    public function size(string $path);

    /**
     * Return the files in a directory.
     *
     * @param string  $dir       The directory to look in.
     * @param boolean $recursive Look for files recursively?.
     * @return string[]
     */
    public function filesInDir(string $dir, bool $recursive = false): array;
}
