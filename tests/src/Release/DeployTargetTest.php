<?php
namespace Crocos\Navy\DeployPlugin\Release;

class DeployTargetTest extends \PHPUnit_Framework_TestCase
{
    protected $target;

    public function setUp()
    {
        $this->target = new DeployTarget(
            'name',
            'commit',
            [ 'parent1', 'parent2' ],
            'yudoufu',
            'flow',
            'owner/repo',
            'branch',
            'http://github.com/owner/repo/pull/1'
        );
    }

    public function testAccessors()
    {
        $this->assertEquals('name', $this->target->getName());
        $this->assertEquals('commit', $this->target->getCommitId());
        $this->assertEquals([ 'parent1', 'parent2' ], $this->target->getParents());
        $this->assertEquals('yudoufu', $this->target->getUser());
        $this->assertEquals('flow', $this->target->getFlowName());
        $this->assertEquals('owner/repo', $this->target->getRepository());
        $this->assertEquals('branch', $this->target->getBaseBranch());
        $this->assertEquals('http://github.com/owner/repo/pull/1', $this->target->getPullRequestUrl());
    }
}
