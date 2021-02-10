<?php

namespace CodeDistortion\Adapt\Support;

use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Provide methods to work with strings.
 */
class StringSupport
{
//    /** @var string[] The renderable parts of an DateInterval. */
//    private const INTERVAL_TYPES = [
//        'y' => 'year',
//        'm' => 'month',
//        'd' => 'day',
//        'h' => 'hour',
//        'i' => 'minute',
//        's' => 'second',
//    ];

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
     * Generate a human-readable version of the given time in seconds.
     *
     * @param DateInterval $interval The amount of time to render.
     * @return string
     */
//    public static function readableInterval(DateInterval $interval): string
//    {
//        $format = [];
//        $started = false;
//        foreach (static::INTERVAL_TYPES as $type => $name) {
//            if (($interval->$type != 0) || ($type == 'i') || ($started)) {
//                $type = $started ? mb_strtoupper($type) : $type; // add preceding '0'
//                $format[] = "%$type";
//                $started = true;
//            }
//        }

//        return $interval->format(implode(':', $format));
//    }

    /**
     * Generate a human-readable version of the given time in seconds.
     *
     * @param DateInterval $interval The amount of time to render.
     * @return string
     */
    public static function vagueReadableInterval(DateInterval $interval): string
    {
        $nowUTC = new DateTime('now', new DateTimeZone('UTC'));
        $laterUTC = (clone $nowUTC)->add($interval);
        $seconds = $laterUTC->getTimestamp() - $nowUTC->getTimestamp();
        if ($seconds < 600) { // < 10 minutes
            return 'soon';
        }
        if ($seconds < 3600) { // < 1 hour
            return 'in a while';
        }
        return 'later on';
    }
}
