<?php
namespace Crocos\Navy\DeployPlugin\Release\Command;

use Crocos\Navy\DeployPlugin\Release\AbstractCommand;

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
