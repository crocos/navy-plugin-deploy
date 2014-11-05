<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Navy\GitHub\WebHook\PullRequest;

class FlowResolver
{
    public function __construct(array $flows)
    {
        $this->flows = $flows;
    }

    public function resolveByPullRequest(PullRequest $pullRequest)
    {
        $comments = $pullRequest->getComments();
        if (count($comments) < 1) {
            return;
        }

        foreach ($this->flows as $flow) {
            foreach ($comments as $comment) {
                if ($this->isFlowComment($pullRequest, $comment, $flow)) {
                    return $flow;
                }
            }
        }

        return;
    }

    public function resolveByDeployTargets(array $deployTargets)
    {
        $targetFlows = [];
        foreach ($deployTargets as $target) {
            $targetFlows[$target->getFlowName()] = true;
        }

        // reverse priority
        foreach (array_reverse($this->flows) as $flow) {
            if (isset($targetFlows[$flow->getName()])) {
                return $flow;
            }
        }

        return;
    }

    protected function isFlowComment($pullRequest, $comment, $flow)
    {
        $matched = false;
        foreach ($flow->getKeywords() as $keyword) {
            if (false !== stripos($comment->getBody(), $keyword)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return false;
        }

        if (!$flow->isPermittedSelfReview()
            && $pullRequest->getUser() === $comment->getUser()
        ) {
            return false;
        }

        return true;
    }
}
