<?php
namespace Crocos\Plugin\DeployPlugin\Release\Command;

use Crocos\Plugin\DeployPlugin\Release\AbstractCommand;

class AliasCommand extends AbstractCommand
{
    protected $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function run()
    {
        return $this->context->run($this->alias);
    }
}
