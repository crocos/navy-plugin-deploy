<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Psr\Log\LoggerInterface;
use Phake;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->queue = Phake::mock(QueueInterface::class);
        $this->controller = Phake::mock(ReleaseController::class);
        $this->logger = Phake::mock(LoggerInterface::class);

        $this->worker = new Worker($this->queue, $this->controller, $this->logger);
    }

    public function testExec()
    {
        $target1 = Phake::mock(DeployTarget::class);
        $target2 = Phake::mock(DeployTarget::class);

        Phake::when($this->queue)->loadTargets()->thenReturn([$target1, $target2]);

        $this->worker->exec();

        Phake::verify($this->controller)->release($target1);
        Phake::verify($this->queue)->remove($target1);
    }

    public function testExecWithNoTargets()
    {
        Phake::when($this->queue)->loadTargets()->thenReturn([]);

        $this->worker->exec();

        Phake::verify($this->controller, Phake::times(0))->release;
        Phake::verify($this->queue, Phake::times(0))->remove;
    }
}
