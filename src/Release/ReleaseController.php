<?php
namespace Crocos\Navy\DeployPlugin\Release;

use Crocos\Navy\DeployPlugin\Shell;
use Navy\BranchMatcher;
use Navy\Notifier\NotifierInterface;
use Psr\Log\LoggerInterface;

class ReleaseController
{
    protected $queue;
    protected $context;
    protected $shell;
    protected $matcher;
    protected $resolver;
    protected $notifier;
    protected $logger;

    public function __construct(
        QueueInterface $queue,
        CommandContext $context,
        Shell $shell,
        BranchMatcher $matcher,
        FlowResolver $resolver,
        NotifierInterface $notifier,
        LoggerInterface $logger
    )
    {
        $this->queue = $queue;
        $this->context = $context;
        $this->shell = $shell;
        $this->matcher = $matcher;
        $this->resolver = $resolver;
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    public function release(DeployTarget $target)
    {
        $repository = $target->getRepository();
        $branch = $target->getBaseBranch();

        $branchConfig = $this->matcher->findBranchConfig($repository, $branch);
        if ($branchConfig === null) {
            throw new \UnexpectedValueException(sprintf('Illigal Queuing. The branch "%s:%s" is not configured.', $repository, $branch));
        }

        $this->shell->setCwd($branchConfig['workdir']);

        $this->context->run('release-open');

        $deployTargets = $this->fetchDeployTargets($repository, $branch);

        $priority = isset($repositoryConfig['priority']) ? $repositoryConfig['priority'] : [];

        $flow = $this->resolver->resolveByDeployTargets($deployTargets);
        if ($flow === null) {
            throw new \UnexpectedValueException(sprintf('Deploy Error. Undefined Flow: %s', $flow->getName()));
        }

        $this->startNotification($deployTargets, $flow);

        foreach ($flow->getProcess() as $command) {
            $this->context->run($command);
        }

        $this->context->run('release-close');

        $this->endNotification($deployTargets);
        $this->removeTargets($deployTargets, $target);
    }

    /**
     * キューに格納されたターゲットから、デプロイ対象となるすべてのターゲットを取得する
     *
     * 通常ターゲットは1件のみだが、複数のPRがほぼ同タイミングでマージされた場合、
     * 複数のターゲットが1度にデプロイされる可能性がある。
     * (git-daily が最新の develop ブランチからリリースを行うため)
     *
     * そのため対象ブランチが同一のターゲットは基本的にすべてがデプロイ対象となる。
     * ここではキューに格納された順でマージされているものとして、
     *
     * * Git のログのうち最後にマージされたPRのログ
     * * 対象のターゲットのうち最後にキューに追加されたもの
     *
     * これらのコミット番号が一致している場合は同期がとれていると見なしてデプロイを進める。
     *
     * 一致しない場合はGitリポジトリへの反映もしくはフックに遅延が発生していると想定し、
     * しばらく待ってからリトライする。もしリトライ回数の上限に達しても一致しない場合は
     * 何らかの問題が発生することとして、 wait 状態にして対応を待つ。
     *
     * @param string $repository
     * @param string $branch
     * @param integer $retry
     * @return array
     *
     * @todo 複雑化してるので別クラスにわけたい
     */
    protected function fetchDeployTargets($repository, $branch, $retry = 0)
    {
        $deployTargets = [];

        try {
            // 先頭のtargetが持っているrepo/branchをdeploy対象として、対象targetを引っ張ってくる
            foreach ($this->queue->loadTargets() as $target) {
                if ($repository === $target->getRepository() && $branch === $target->getBaseBranch()) {
                    $deployTargets[] = $target;
                }
            }

            if (empty($deployTargets)) {
                throw new \UnexpectedValueException('queue load error. deploy target queue is empty.');
            }

            $mergeLog = $this->context->run("pr-merge-log(1)");

            $headCommitId = end($deployTargets)->getParents()['head'];

            if (false === strpos($mergeLog, $headCommitId)) {
                if ($retry++ < 3) {
                    // Github Hookが追い付いていない可能性があるので、3回までwaitして自動retry.
                    $this->logger->info('not queueing merge exist. retry:' . $retry . ' queue fetch...');
                    sleep(1);
                    $deployTargets = $this->fetchDeployTargets($repository, $branch, $retry);
                } else {
                    // 3回retryしてダメだったら、遅延でもないしなんか問題あるので人がチェック.
                    throw new \RuntimeException('not queueing merge exist. retry:' . $retry . ' gave up autoretry.');
                }
            }

        // todo 例外処理はもうちょいインテンショナルに...
        } catch (\UnexpectedValueException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            $this->logger->warning($e->getMessage());

            $this->context->run('check');
            $this->context->run('watch');

            $deployTargets = $this->fetchDeployTargets($repository, $branch);
        }

        return array_filter($deployTargets);
    }

    protected function fixFlowName($name)
    {
        return ($name === 'plusone' ? '+1' : $name);
    }

    /**
     * @todo 文言どうにかしないとなあ
     */
    protected function startNotification(array $deployTargets, FlowInterface $flow)
    {
        $prURLs = $users = [];
        foreach ($deployTargets as $target) {
            $prURLs[] = $target->getPullRequestUrl() . ' ' . $target->getUser() . ' ' . $flow->getName();
            $users[] = '@' . $target->getUser();
        }

        $message = <<<'EOL'
リリースを`{flow}`のフローで開始します！ログは`{sudo} navy tail`してね > {users}

対象はこちら:
{urls}

{releaselist}
EOL;

        $replacements = [
            '{flow}'        => $flow->getName(),
            '{sudo}'        => $this->context->getEnv('sudo'),
            '{users}'       => implode(' ', array_unique($users)),
            '{urls}'        => implode(PHP_EOL, $prURLs),
            '{releaselist}' => $this->context->run('release-list'),
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

        $this->notifier->notify($message);
    }

    protected function endNotification(array $deployTargets)
    {
        foreach ($deployTargets as $target) {
            $prURLs[] = $target->getPullRequestUrl();
        }

        $this->notifier->notify('リリース完了！ (*ﾟ∇ﾟ)_∠※☆PAN！' . PHP_EOL . implode(PHP_EOL, $prURLs));
    }

    protected function removeTargets(array $deployTargets, DeployTarget $origTarget)
    {
        foreach ($deployTargets as $target) {
            if ($origTarget->getName() === $target->getName()) {
                continue;
            }

            $this->queue->remove($target);
        }
    }
}
