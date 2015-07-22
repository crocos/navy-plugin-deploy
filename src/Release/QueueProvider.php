<?php
namespace Crocos\Navy\DeployPlugin\Release;

class QueueProvider
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get()
    {
        if (!isset($this->config['type'])) {
            throw new \InvalidArgumentException('Required "type" for queue config');
        }

        switch ($this->config['type']) {
            case 'file':
                return new FileQueue($this->config['dir']);

            default:
                throw new \InvalidArgumentException(sprintf('Undefined queue type "%s"', $this->config['type']));
        }
    }
}
