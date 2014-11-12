<?php
namespace Crocos\Navy\DeployPlugin;

use Navy\Plugin\AbstractPlugin;

class DeployPlugin extends AbstractPlugin
{
    public function getHooks()
    {
        return [
            'deploy.hook.queuing',
            'deploy.hook.releaselog',
        ];
    }
}
