<?php
namespace Crocos\Plugin\DeployPlugin\Release;

interface FlowInterface
{
    public function getName();
    public function getKeywords();
    public function isPermittedSelfReview();
    public function getProcess();
}
