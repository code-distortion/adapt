<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use Illuminate\Support\Facades\Log;

/**
 * Injectable class to abstract logging actions.
 */
class LaravelLog implements LogInterface
{
    /** @var float[] Internal timers. */
    private $timers = [];

    /** @var boolean Should messages be displayed via std-out?. */
    private $stdout;

    /** @var boolean Should messages be sent to Laravel's logging mechanism?. */
    private $laravel;


    /**
     * Constructor.
     *
     * @param boolean $stdout  Display messages to stdout?.
     * @param boolean $laravel Add messages to Laravel's standard log.
     */
    public function __construct(bool $stdout, bool $laravel)
    {
        $this->stdout = $stdout;
        $this->laravel = $laravel;
    }

    /**
     * Display some debug output - DEBUG level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function debug($message, $timerRef = null)
    {
        $this->output('debug', $this->buildMessage($message, $timerRef));
    }

    /**
     * Display some debug output - WARNING level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function warning($message, $timerRef = null)
    {
        $this->output('warning', $this->buildMessage($message, $timerRef));
    }

    /**
     * Display some debug output - ERROR level.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return void
     */
    public function error($message, $timerRef = null)
    {
        $this->output('error', $this->buildMessage($message, $timerRef));
    }

    /**
     * Actually log the message to the desired locations.
     *
     * @param string $logLevel The level to log the message at.
     * @param string $message  The message to log.
     * @return void
     */
    private function output(string $logLevel, string $message)
    {
        if ($this->stdout) {
            print 'ADAPT ' . mb_strtoupper($logLevel) . ': ' . $message . PHP_EOL;
        }
        if ($this->laravel) {
            Log::$logLevel('ADAPT: ' . $message);
        }
    }

    /**
     * Create a new timer and return a reference to it.
     *
     * @return integer
     */
    public function newTimer(): int
    {
        $index = count($this->timers);
        $this->timers[$index] = microtime(true);
        return $index;
    }

    /**
     * Return the duration of a timer.
     *
     * @param integer|null $timerRef The timer to get the time taken from.
     * @return float|null
     */
    public function getDuration($timerRef = null)
    {
        return isset($this->timers[$timerRef])
            ? microtime(true) - $this->timers[$timerRef]
            : null;
    }

    /**
     * Take the time and render it as a string.
     *
     * @param integer|null $timerRef The timer to get the time taken from.
     * @return string
     */
    private function formatTime(int $timerRef = null): string
    {
        $timeTaken = $this->getDuration($timerRef);
        return (!is_null($timeTaken) ? ' (' . round($timeTaken * 1000) . 'ms)' : '');
    }

    /**
     * Format the message ready for outputting.
     *
     * @param string       $message  The message to show.
     * @param integer|null $timerRef Show the time taken for the given timer.
     * @return string
     */
    private function buildMessage(string $message, int $timerRef = null): string
    {
        return $message . $this->formatTime($timerRef);

//        $caller = debug_backtrace()[2];
//        $temp = explode('\\', $caller['class']);
//        $class = array_pop($temp);
//        return $class . '::' . $caller['function'] . '(): ' . $message . $this->formatTime($timerRef);
    }



    /**
     * Add the array keys to the values, padded based on the length of the longest key.
     *
     * @param array<string, string> $lines The lines to process.
     * @return void
     */
    public function padList($lines): array
    {
        $maxLength = 0;
        foreach (array_keys($lines) as $key) {
            $maxLength = max($maxLength, mb_strlen($key));
        }

        $newLines = [];
        foreach ($lines as $key => $line) {
            $line = str_replace(["\r\n", "\r", "\n"], "\n", $line);
            $partialLines = explode("\n", $line);
            $count = 0;
            foreach ($partialLines as $partialLine) {
                $tempKey = $count++ == 0 ? $key : '';
                $newLines[] = str_pad($tempKey, $maxLength + 1, ' ', STR_PAD_RIGHT) . $partialLine;
            }
        }

        return $newLines;
    }

    /**
     * Log some lines in a box.
     *
     * @param string|string[] $lines The lines to log in a table.
     * @param string|null     $title The title to add to the top line.
     * @param string          $level The logging level to use.
     * @return void
     */
    public function logBox($lines, $title = null, $level = 'debug')
    {
        $lines = !is_array($lines) ? [$lines] : $lines;

        if (!count(array_filter($lines))) {
            return;
        }

        $title = mb_strlen($title) ? " $title " : '';

        $maxLength = mb_strlen($title);
        foreach ($lines as $line) {
            $maxLength = max($maxLength, mb_strlen($line));
        }

        $this->{$level}('┌──' . $title . str_repeat('─', $maxLength - mb_strlen($title)) . '┐');

        foreach ($lines as $line) {
            $line = str_pad($line, $maxLength, ' ', STR_PAD_RIGHT);
            $this->{$level}("│ $line │");
        }

        $this->{$level}('└' . str_repeat('─', $maxLength + 2) . '┘');
    }
}
