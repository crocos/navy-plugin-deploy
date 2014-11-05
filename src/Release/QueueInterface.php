<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Navy\GitHub\WebHook\PullRequest;

interface QueueInterface
{
    public function save(PullRequest $pullRequest, FlowInterface $flow);
    public function loadTargets();
    public function remove(DeployTarget $target);
}
