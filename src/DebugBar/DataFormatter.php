<?php

namespace BootPress\DebugBar;

use DebugBar\DataFormatter\DataFormatterInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DataFormatter implements DataFormatterInterface
{
    /**
     * DataFormatter constructor.
     */
    public function __construct()
    {
        $this->cloner = new VarCloner();
        $this->cloner->setMaxItems(100);
        $this->cloner->setMaxString(100);
        $this->dumper = new CliDumper();
    }

    /**
     * @param $data
     * @return string
     */
    public function formatVar($data)
    {
        $output = '';

        $this->dumper->dump(
            $this->cloner->cloneVar($data),
            function ($line, $depth) use (&$output) {
                // A negative depth means "end of dump"
                if ($depth >= 0) {
                    // Add four spaces of indentation to the line
                    $output .= str_repeat("\t", $depth).$line."\n";
                }
            }
        );

        return trim($output);
    }

    /**
     * @param float $seconds
     * @return string
     */
    public function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . ' μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 1) . ' ms';
        }
        return round($seconds, 2) . ' s';
    }

    /**
     * @param string $size
     * @param int $precision
     * @return string
     */
    public function formatBytes($size, $precision = 1)
    {
        if ($size === 0 || $size === null) {
            return "0 B";
        }

        $sign = $size < 0 ? '-' : '';
        $size = abs($size);

        $base = log($size) / log(1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return $sign . round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
