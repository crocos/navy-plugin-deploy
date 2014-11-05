<?php
namespace Crocos\Navy\DeployPlugin\Hook;

use Navy\GitHub\WebHook\PullRequest;
use Navy\Hook\HookInterface;
use Navy\Hook\PullRequestEvent;
use Navy\BranchMatcher;
use Crocos\Navy\DeployPlugin\Release\FlowResolver;
use Crocos\Navy\DeployPlugin\Release\QueueInterface;

class QueuingHook implements HookInterface
{
    protected $queue;
    protected $matcher;
    protected $resolver;

    public function __construct(QueueInterface $queue, BranchMatcher $matcher, FlowResolver $resolver)
    {
        $this->queue = $queue;
        $this->matcher = $matcher;
        $this->resolver = $resolver;
    }

    public function getEvent()
    {
        return 'pull_request';
    }

    public function onPullRequest(PullRequestEvent $event)
    {
        if ($event->getAction() !== 'closed') {
            return;
        }

        $pullRequest = $event->getPullRequest();

        if (!$this->isTarget($pullRequest)) {
            return;
        }

        $flow = $this->resolver->resolveByPullRequest($pullRequest);
        if ($flow === null) {
            return;
        }

        $this->queue->save($pullRequest, $flow);
    }

    protected function isTarget(PullRequest $pullRequest)
    {
        if (!$pullRequest->isMerged()) {
            return false;
        }

        return $this->matcher->matchBranch($pullRequest->getRepository()->getFullName(), $pullRequest->getBaseBranch());
    }
}
