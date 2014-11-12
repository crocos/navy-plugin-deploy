<?php
namespace Crocos\Navy\DeployPlugin\Release;

interface FlowInterface
{
    public function getName();
    public function getKeywords();
    public function isPermittedSelfReview();
    public function getProcess();
}
