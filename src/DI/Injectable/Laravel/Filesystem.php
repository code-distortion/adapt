<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\FilesystemInterface;
use DirectoryIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Injectable class to abstract interaction with the filesystem.
 */
class Filesystem implements FilesystemInterface
{
    /**
     * Check whether the given path exists.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function pathExists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check whether the given path exists and is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function fileExists(string $path): bool
    {
        return ((file_exists($path)) && (is_file($path)));
    }

    /**
     * Check whether the given path exists and is a directory.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function dirExists(string $path): bool
    {
        return ((file_exists($path)) && (is_dir($path)));
    }

    /**
     * Check whether the given path is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Touch the given file.
     *
     * @param string $path The path to touch.
     * @return boolean
     */
    public function touch(string $path): bool
    {
        return touch($path);
    }

    /**
     * Returns canonicalized absolute pathname.
     *
     * @param string $path The path being checked.
     * @return string|null
     */
    public function realpath(string $path): ?string
    {
        $path = realpath($path);
        return (is_string($path) ? $path : null);
    }

    /**
     * Remove the current path prefix from the given path.
     *
     * @param string      $path     The path to alter.
     * @param string|null $basePath The base-path prefix to remove.
     * @return string
     */
    public function removeBasePath(string $path, ?string $basePath = null): string
    {
        $basePath ??= realpath('.');
        $basePath = rtrim((string) $basePath, '/') . '/';

        if (mb_substr($path, 0, mb_strlen($basePath)) == $basePath) {
            return mb_substr($path, mb_strlen($basePath));
        }
        return $path;
    }

    /**
     * Write to the given file.
     *
     * @param string $path    The path of the file to write to.
     * @param string $mode    The write-mode.
     * @param string $content The content to write.
     * @return boolean
     */
    public function writeFile(string $path, string $mode, string $content): bool
    {
        if ($fp = fopen($path, $mode)) {
            fwrite($fp, $content);
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * Copy the given file to another location.
     *
     * @param string $srcPath  The path to copy.
     * @param string $destPath The destination location.
     * @return boolean
     */
    public function copy(string $srcPath, string $destPath): bool
    {
        return copy($srcPath, $destPath);
    }

    /**
     * Rename a file.
     *
     * @param string $srcPath  The path to the file to rename.
     * @param string $destPath The destination name.
     * @return boolean
     */
    public function rename(string $srcPath, string $destPath): bool
    {
        return rename($srcPath, $destPath);
    }

    /**
     * Delete the given file.
     *
     * @param string $path The path to delete.
     * @return boolean
     */
    public function unlink(string $path): bool
    {
        return unlink($path);
    }

    /**
     * Create a directory.
     *
     * @param string  $pathname  The directory path.
     * @param integer $mode      The mode to set the directory to.
     * @param boolean $recursive Allows the creation of nested directories specified in the pathname.
     * @return boolean
     */
    public function mkdir(string $pathname, int $mode = 0777, bool $recursive = false): bool
    {
        return mkdir($pathname, $mode, $recursive);
    }

    /**
     * Generate an md5 of the given file.
     *
     * @param string $path The path of the file to hash.
     * @return string|null
     */
    public function md5File(string $path): ?string
    {
        $md5 = md5_file($path);
        return (is_string($md5) ? $md5 : null);
    }

    /**
     * Get the size of the file in bytes.
     *
     * @param string $path The path of the file to get the size of.
     * @return integer|null
     */
    public function size(string $path): ?int
    {
        $size = filesize($path);
        return is_integer($size) ? $size : null;
    }

    /**
     * Return the files in a directory.
     *
     * @param string  $dir       The directory to look in.
     * @param boolean $recursive Look for files recursively?.
     * @return string[]
     */
    public function filesInDir(string $dir, bool $recursive = false): array
    {
        if ($recursive) {
            $dirIterator = new RecursiveDirectoryIterator($dir);
            $fileIterator = new RecursiveIteratorIterator($dirIterator);
        } else {
            $dirIterator = new DirectoryIterator($dir);
            $fileIterator = new IteratorIterator($dirIterator);
        }

        $files = [];
        foreach ($fileIterator as $file) {
            if (is_file($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }
}
