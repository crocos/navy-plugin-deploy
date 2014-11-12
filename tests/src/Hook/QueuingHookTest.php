<?php
namespace Crocos\Navy\DeployPlugin\Hook;

use Navy\GitHub\WebHook as Gh;
use Navy\Hook\PullRequestEvent;
use Navy\BranchMatcher;
use Crocos\Navy\DeployPlugin\Release\QueueInterface;
use Crocos\Navy\DeployPlugin\Release\FlowInterface;
use Crocos\Navy\DeployPlugin\Release\FlowResolver;
use Phake;

class QueuingHookTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->queue = Phake::mock(QueueInterface::class);
        $this->matcher = Phake::mock(BranchMatcher::class);
        $this->resolver = Phake::mock(FlowResolver::class);

        $this->hook = new QueuingHook($this->queue, $this->matcher, $this->resolver);
    }

    /**
     * @dataProvider getOnPullRequestData
     */
    public function testOnPullRequest($saved, $action, $merged, $matchRepository)
    {
        $repo = Phake::mock(Gh\Repository::class);
        Phake::when($repo)->getFullName()->thenReturn('owner/repo');

        $pr = Phake::mock(Gh\PullRequest::class);
        Phake::when($pr)->isMerged()->thenReturn($merged);
        Phake::when($pr)->getBaseBranch()->thenReturn('master');
        Phake::when($pr)->getRepository()->thenReturn($repo);

        Phake::when($this->matcher)->matchBranch('owner/repo', 'master')->thenReturn($matchRepository);

        $flow = Phake::mock(FlowInterface::class);
        Phake::when($this->resolver)->resolveByPullRequest($pr)->thenReturn($flow);

        // execute
        $event = Phake::mock(PullRequestEvent::class);
        Phake::when($event)->getPullRequest()->thenReturn($pr);
        Phake::when($event)->getAction()->thenReturn($action);

        $this->hook->onPullRequest($event);

        Phake::verify($this->queue, Phake::times($saved ? 1 : 0))->save($pr, $flow);
    }

    public function getOnPullRequestData()
    {
        return [
            // saved?,  action,     merged?,    match?
            [true,      'closed',   true,       true],
            [false,     'oops',     true,       true],
            [false,     'closed',   false,      true],
            [false,     'closed',   true,       false],
        ];
    }
}
