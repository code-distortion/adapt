<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\FilesystemInterface;
use DirectoryIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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
    public function pathExists($path): bool
    {
        return file_exists($path);
    }

    /**
     * Check whether the given path exists and is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function fileExists($path): bool
    {
        return ((file_exists($path)) && (is_file($path)));
    }

    /**
     * Check whether the given path exists and is a directory.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function dirExists($path): bool
    {
        return ((file_exists($path)) && (is_dir($path)));
    }

    /**
     * Check whether the given path is a file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    public function isFile($path): bool
    {
        return is_file($path);
    }

    /**
     * Touch the given file.
     *
     * @param string $path The path to touch.
     * @return boolean
     */
    public function touch($path): bool
    {
        return touch($path);
    }

    /**
     * Returns canonicalized absolute pathname.
     *
     * @param string $path The path being checked.
     * @return string|null
     */
    public function realpath($path)
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
    public function removeBasePath($path, $basePath = null): string
    {
        $basePath = $basePath ?? realpath('.');
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
    public function writeFile($path, $mode, $content): bool
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
    public function copy($srcPath, $destPath): bool
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
    public function rename($srcPath, $destPath): bool
    {
        return rename($srcPath, $destPath);
    }

    /**
     * Delete the given file.
     *
     * @param string $path The path to delete.
     * @return boolean
     */
    public function unlink($path): bool
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
    public function mkdir($pathname, $mode = 0777, $recursive = false): bool
    {
        return mkdir($pathname, $mode, $recursive);
    }

    /**
     * Generate an md5 of the given file.
     *
     * @param string $path The path of the file to hash.
     * @return string|null
     */
    public function md5File($path)
    {
        $md5 = md5_file($path);
        return (is_string($md5) ? $md5 : null);
    }

    /**
     * Find the time when the file was last modified.
     *
     * @param string $path The path of the file to check.
     * @return integer|null
     */
    public function fileModifiedTime($path)
    {
        $return = filemtime($path);
        return is_int($return) ? $return : null;
    }

    /**
     * Get the size of the file in bytes.
     *
     * @param string $path The path of the file to get the size of.
     * @return integer|null
     */
    public function size($path)
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
    public function filesInDir($dir, $recursive = false): array
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
            /** @var SplFileInfo $file */
            if (is_file($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }
}
