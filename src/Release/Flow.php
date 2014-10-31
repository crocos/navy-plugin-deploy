<?php
namespace Crocos\Plugin\DeployPlugin\Release;

class Flow implements FlowInterface
{
    protected $name;
    protected $config;

    public function __construct($name, array $config)
    {
        $this->name = $name;
        $this->setConfig($config);
    }

    public function setConfig(array $config)
    {
        $config = array_merge([
            'keyword'            => [],
            'permit_self_review' => false,
            'process'            => [],
        ], $config);

        if (count($config['process']) < 1) {
            throw new \InvalidArgumentException(sprintf('Process is required for flow "%s"', $this->getName()));
        }

        $this->config = $config;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getKeywords()
    {
        return (array) $this->config['keyword'];
    }

    public function isPermittedSelfReview()
    {
        return $this->config['permit_self_review'];
    }

    public function getProcess()
    {
        return $this->config['process'];
    }
}
