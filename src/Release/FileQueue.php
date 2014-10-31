<?php
namespace Crocos\Plugin\DeployPlugin\Release;

use Navy\GitHub\WebHook\PullRequest;
use DirectoryIterator;

class FileQueue implements QueueInterface
{
    protected $queueDir;

    public function __construct($queueDir)
    {
        $this->queueDir = $queueDir;
    }

    public function save(PullRequest $pullRequest, FlowInterface $flow)
    {
        $this->makeQueueDir();

        $target = $this->createTarget($pullRequest, $flow);

        $filename = $this->getQueuePath($target->getName());

        file_put_contents($filename, json_encode($target->toArray()));
        chmod($filename, 0666);
    }

    public function loadTargets()
    {
        $this->makeQueueDir();

        $targets = [];

        $dir = new DirectoryIterator($this->queueDir);
        foreach ($dir as $file) {
            if ($file->isFile() && $file->getFilename()[0] !== '.') {
                $targets[$file->getFilename()] = $this->loadQueue(file_get_contents($file->getPathname()));
            }
        }
        ksort($targets);

        return array_values($targets);
    }

    public function remove(DeployTarget $target)
    {
        $path = $this->getQueuePath($target->getName());

        if (!file_exists($path)) {
            throw new \RuntimeException('File ' . $path . 'Not Found. c\'ant remove. ');
        }

        unlink($path);
    }

    protected function loadQueue($content)
    {
        return DeployTarget::createFromArray((array) json_decode($content, true));
    }

    protected function createTarget(PullRequest $pullRequest, FlowInterface $flow)
    {
        $name = $this->createQueueName($flow->getName());

        $commitId   = $pullRequest->getMergeCommitId();
        $parents    = [ 'base' => $pullRequest->getBaseCommitId(), 'head' => $pullRequest->getHeadCommitId() ];
        $user       = $pullRequest->getUser();
        $flowName   = $flow->getName();
        $repository = $pullRequest->getRepository()->getOwner() . '/' . $pullRequest->getRepository()->getName();
        $baseBranch = $pullRequest->getBaseBranch();
        $url        = $pullRequest->getHtmlUrl();

        return new DeployTarget($name, $commitId, $parents, $user, $flowName, $repository, $baseBranch, $url);
    }

    protected function getQueuePath($filename)
    {
        return $this->queueDir . '/' . $filename;
    }

    protected function createQueueName($flowName)
    {
        return 'queue-' . date('YmdHis') . '-' . $flowName . '-' . mt_rand();
    }

    protected function makeQueueDir()
    {
        if (!file_exists($this->queueDir)) {
            mkdir($this->queueDir, 0777, true);
            chmod($this->queueDir, 0777);
        }

        if (!is_dir($this->queueDir)) {
            throw new \RuntimeException(sprintf('Cannot make queue directory "%s"', $this->queueDir));
        }
    }
}
