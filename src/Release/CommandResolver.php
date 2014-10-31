<?php
namespace Crocos\Plugin\DeployPlugin\Release;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Crocos\Plugin\DeployPlugin\Release\Command\AliasCommand;
use Crocos\Plugin\DeployPlugin\Release\Command\ExecCommand;
use Crocos\Plugin\DeployPlugin\Shell;
use Psr\Log\LoggerInterface;

class CommandResolver
{
    protected $context;
    protected $shell;
    protected $container;
    protected $logger;

    public function __construct(CommandContext $context, Shell $shell, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->shell = $shell;
        $this->container = $container;
        $this->logger = $logger;
    }

    public function resolve(array $data)
    {
        foreach ($data as $name => $body) {
            $command = null;
            if (is_array($body)) {
                $command = $this->parseArrayBody($body);
            } elseif (is_string($body)) {
                $command = $this->parseStringBody($body);
            } else {
                throw new InvalidArgumentException(sprintf('Cannot parse "%s" (%s)', $name, var_export($body, true)));
            }

            $command->setLogger($this->logger);

            $this->context->set($name, $command);
        }
    }

    protected function parseArrayBody($body)
    {
        return new ExecCommand($this->shell, $body);
    }

    protected function parseStringBody($body)
    {
        if (strpos($body, '@') === 0) {
            return $this->getService(substr($body, 1));
        }

        return new AliasCommand($body);
    }

    protected function getService($id)
    {
        return $this->container->get($id);
    }
}
