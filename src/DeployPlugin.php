<?php
namespace Crocos\Navy\DeployPlugin;

use Navy\Plugin\AbstractPlugin;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DeployPlugin extends AbstractPlugin
{
    public function loadConfig(ParameterBagInterface $parameters, array $config)
    {
        $this->loadReleaseConfig($parameters, $config['release']);
        $this->loadReleaselogConfig($parameters, $config['releaselog']);
    }

    public function getHooks()
    {
        return [
            'deploy.hook.queuing',
            'deploy.hook.releaselog',
        ];
    }

    protected function loadReleaseConfig($parameters, $config)
    {
        $params = [
            'deploy.release.repository'       => [],
            'deploy.release.flow'             => [],
            'deploy.release.queue.config'     => [],
            'deploy.release.command.lockfile' => null,
            'deploy.release.command.env'      => [],
        ];

        if (isset($config['repository'])) {
            $params['deploy.release.repository'] = $config['repository'];
        }

        if (isset($config['flow'])) {
            $params['deploy.release.flow'] = $config['flow'];
        }

        if (isset($config['queue'])) {
            $params['deploy.release.queue.config'] = $config['queue'];
        }

        if (isset($config['command']['lockfile'])) {
            $params['deploy.release.command.lockfile'] = $config['command']['lockfile'];
        }

        if (isset($config['command']['env'])) {
            $params['deploy.release.command.env'] = $config['command']['env'];
        }

        $parameters->add($params);
    }

    protected function loadReleaselogConfig($parameters, $config)
    {
        $params = [
            'deploy.releaselog.repository'      => [],
            'deploy.releaselog.logger.config'   => [],
            'deploy.releaselog.notifier.config' => [],
        ];

        if (isset($config['repository'])) {
            $params['deploy.releaselog.repository'] = $config['repository'];
        }

        if (isset($config['logger'])) {
            $params['deploy.releaselog.logger.config'] = $config['logger'];
        }

        if (isset($config['notifier'])) {
            $params['deploy.releaselog.notifier.config'] = $config['notifier'];
        }

        $parameters->add($params);
    }
}
