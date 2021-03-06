<?php
namespace Crocos\Navy\DeployPlugin\Release\Command;

use Crocos\Navy\DeployPlugin\Release\AbstractCommand;

class SailCommand extends AbstractCommand
{
    public function __construct($lockFile)
    {
        $this->lockFile = $lockFile;
    }

    public function run()
    {
        $this->logger->info(sprintf('Sail: "%s"', $this->lockFile));

        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
}
