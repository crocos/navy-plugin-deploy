<?php
namespace Crocos\Plugin\DeployPlugin\Release;

use Psr\Log\LoggerInterface;

interface CommandInterface
{
    public function run();
    public function setContext(CommandContext $context);
    public function setArgs(array $args);
    public function setLogger(LoggerInterface $logger);
}
