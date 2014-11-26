<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Navy\Notifier\NotifierInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractCommand implements CommandInterface
{
    protected $context;
    protected $args = [];
    protected $logger;

    public function setContext(CommandContext $context)
    {
        $this->context = $context;

        return $this;
    }

    public function setArgs(array $args)
    {
        $this->args = $args;
    }

    public function setNotifier(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
