<?php
namespace Crocos\Plugin\DeployPlugin\Release\Command;

use Crocos\Plugin\DeployPlugin\Release\AbstractCommand;

class AnchorCommand extends AbstractCommand
{
    public function __construct($lockFile)
    {
        $this->lockFile = $lockFile;
    }

    public function run()
    {
        $this->logger->info(sprintf('Anchor: "%s"', $this->lockFile));

        if (!file_exists($this->lockFile)) {
            touch($this->lockFile);
        }
    }
}
