<?php
namespace Crocos\Navy\DeployPlugin\Release;

class DeployTarget
{
    protected $name;
    protected $commitId;
    protected $parents;
    protected $user;
    protected $flowName;
    protected $repository;
    protected $baseBranch;
    protected $pullRequestUrl;

    public function __construct($name, $commitId, $parents, $user, $flowName, $repository, $baseBranch, $url)
    {
        $this->name = $name;
        $this->commitId = $commitId;
        $this->parents = $parents;
        $this->user = $user;
        $this->flowName = $flowName;
        $this->repository = $repository;
        $this->baseBranch = $baseBranch;
        $this->pullRequestUrl = $url;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCommitId()
    {
        return $this->commitId;
    }

    public function getParents()
    {
        return $this->parents;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getFlowName()
    {
        return $this->flowName;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getBaseBranch()
    {
        return $this->baseBranch;
    }

    public function getPullRequestUrl()
    {
        return $this->pullRequestUrl;
    }

    public function toArray()
    {
        return [
            'name'           => $this->name,
            'commitId'       => $this->commitId,
            'parents'        => $this->parents,
            'user'           => $this->user,
            'flowName'       => $this->flowName,
            'repository'     => $this->repository,
            'baseBranch'     => $this->baseBranch,
            'pullRequestUrl' => $this->pullRequestUrl,
        ];
    }

    public static function createFromArray(array $data)
    {
        return new static(
            $data['name'],
            $data['commitId'],
            $data['parents'],
            $data['user'],
            $data['flowName'],
            $data['repository'],
            $data['baseBranch'],
            $data['pullRequestUrl']
        );
    }
}
