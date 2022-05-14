<?php

namespace CodeDistortion\Adapt\Support;

/**
 * Provide methods to work with strings.
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
    public static function readableSize(int $bytes, int $decPl = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((mb_strlen((string) $bytes) - 1) / 3);

        return round($bytes / pow(1024, $factor), $decPl) . @$size[$factor];
    }

    /**
     * Perform a str_replace(..) but only for the last occurrence.
     *
     * @param string $search  The string to search for.
     * @param string $replace The string to replace it with.
     * @param string $subject The string to search through.
     * @return string
     */
    public static function strReplaceLast(string $search, string $replace, string $subject): string
    {
        $pos = mb_strpos(strrev($subject), strrev($search));
        $pos += mb_strlen($search);
        $pos = mb_strlen($subject) - $pos;

        $start = mb_substr($subject, 0, $pos);
        $end = mb_substr($subject, $pos);

        // perform the replacement only in the last portion of the string
        $end = str_replace($search, $replace, $end);

        return "$start$end";
    }
}
