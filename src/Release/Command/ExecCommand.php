<?php
namespace Crocos\Plugin\DeployPlugin\Release\Command;

use Crocos\Plugin\DeployPlugin\Release\AbstractCommand;
use Crocos\Plugin\DeployPlugin\Shell;

class ExecCommand extends AbstractCommand
{
    protected $shell;
    protected $cmds = [];

    public function __construct(Shell $shell, array $cmds)
    {
        $this->shell = $shell;
        $this->cmds = $cmds;
    }

    public function run()
    {
        $search  = [];
        $replace = [];
        foreach (array_merge($this->context->getEnv(), $this->args) as $key => $value) {
            $search[]  = '{' . $key . '}';
            $replace[] = $value;
        }

        $lastResult = null;
        foreach ($this->cmds as $cmd) {
            // parse mustache
            $cmd = str_replace($search, $replace, $cmd);

            $lastResult = $this->execCommand($cmd);
        }

        return $lastResult;
    }

    protected function execCommand($cmd)
    {
        $this->logger->info(sprintf('Execute command `%s`', $cmd));

        $this->context->run('watch');

        $result = '';
        $status = $this->shell->run($cmd, function ($v) use (&$result) { $result .= $v; });

        if ($status !== 0) {
            $this->logger->error('Command Failed!: ' . $cmd);

            $this->context->run('check');
            $this->context->run('watch');

            $this->logger->warning('resume ... Command Retry: ' . $cmd);

            $result = $this->execCommand($cmd);
        }

        return $result;
    }
}
