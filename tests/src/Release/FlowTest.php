<?php
namespace Crocos\Navy\DeployPlugin\Release;

class FlowTest extends \PHPUnit_Framework_TestCase
{
    public function testSetConfig()
    {
        $flow = new Flow('name', [
            'keyword' => 'key',
            'process' => [
                'do',
                'something',
            ],
        ]);

        $this->assertEquals('name', $flow->getName());
        $this->assertFalse($flow->isPermittedSelfReview());
        $this->assertEquals(['key'], $flow->getKeywords());
        $this->assertEquals(['do', 'something'], $flow->getProcess());
    }
}
