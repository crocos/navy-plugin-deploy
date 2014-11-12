<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Psr\Log\LoggerInterface;

class Worker
{
    protected $queue;
    protected $controller;
    protected $logger;

    public function __construct(QueueInterface $queue, ReleaseController $controller, LoggerInterface $logger)
    {
        $this->queue = $queue;
        $this->controller = $controller;
        $this->logger = $logger;
    }

    public function exec()
    {
        try {
            $this->ship();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    protected function ship()
    {
        $targets = $this->queue->loadTargets();

        if (count($targets) < 1) {
            return;
        }

        $target = array_shift($targets);

        $this->controller->release($target);

        $this->logger->debug('released. remove queue ' . $target->getName());
        $this->queue->remove($target);
    }
}
