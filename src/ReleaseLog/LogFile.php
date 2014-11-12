<?php
namespace Crocos\Navy\DeployPlugin\ReleaseLog;

class LogFile
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function getName($period)
    {
        return $this->config['dir'] . DIRECTORY_SEPARATOR . $this->config['prefix'] . $period;
    }

    protected function createFile($filename)
    {
        if (! file_exists($this->config['dir'])) {
            mkdir($dir, $this->config['permission']);
        }

        touch($filename);
        chmod($filename, $this->config['permission']);
    }

    public function append($period, $log)
    {
        $filename = $this->getName($period);

        if (! file_exists($filename)) {
            $this->createFile($filename);
        }

        file_put_contents($filename, $log, FILE_APPEND);
    }

    public function read($period)
    {
        $filename = $this->getName($period);

        $log = '';
        if (file_exists($filename)) {
            $log = file_get_contents($filename);
        }

        return $log;
    }
}
