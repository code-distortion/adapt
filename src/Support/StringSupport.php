<?php

namespace CodeDistortion\Adapt\Support;

/**
 * Provide methods to work with strings.
 *
 */
class StringSupport
{
    /**
     * Generate a human-readable version of the given size in bytes.
     *
     * @param integer $bytes The bytes to represent.
     * @param integer $decPl The number of decimal places to use.
     * @return string
     */
    public static function readableSize(int $bytes, int $decPl = 2)
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((mb_strlen((string) $bytes) - 1) / 3);

        return round($bytes / pow(1024, $factor), $decPl).@$size[$factor];
    }
}
