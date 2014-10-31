<?php
namespace Crocos\Plugin\DeployPlugin\Hook;

use Navy\GitHub\WebHook\PullRequest;
use Navy\Hook\HookInterface;
use Navy\Hook\PullRequestEvent;
use Navy\BranchMatcher;
use Crocos\Plugin\DeployPlugin\ReleaseLog\Logger;
use Crocos\Plugin\DeployPlugin\ReleaseLog\Notifier;

class ReleaseLogHook implements HookInterface
{
    protected $logger;
    protected $notifier;
    protected $matcher;

    public function __construct(Logger $logger, Notifier $notifier, BranchMatcher $matcher)
    {
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->matcher = $matcher;
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

        $this->logger->log($pullRequest);
        $this->notifier->notify($pullRequest);
    }

    /**
     * release logを拾うターゲットブランチかどうか
     */
    protected function isTarget(PullRequest $pullRequest)
    {
        if (!$pullRequest->isMerged()) {
            return false;
        }

        return $this->matcher->matchBranch($pullRequest->getRepository()->getFullName(), $pullRequest->getBaseBranch());
    }
}
