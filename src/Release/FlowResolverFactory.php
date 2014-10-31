<?php
namespace Crocos\Plugin\DeployPlugin\Release;

class FlowResolverFactory
{
    public function __construct(array $configFlows)
    {
        $this->configFlows = $configFlows;
    }

    public function create()
    {
        $flows = [];

        foreach ($this->configFlows as $name => $config) {
            $flows[] = new Flow($name, $config);
        }

        $resolver = new FlowResolver($flows);

        return $resolver;
    }
}
