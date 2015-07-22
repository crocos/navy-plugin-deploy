<?php
namespace Crocos\Navy\DeployPlugin\Release\Command;

use Crocos\Navy\DeployPlugin\Release\AbstractCommand;

class WatchCommand extends AbstractCommand
{
    const WAIT = 5;
    const LIMIT = 720; // LIMIT * WAIT = wait time limit(3600 second).

    public function __construct($lockFile)
    {
        $this->lockFile = $lockFile;
    }

    public function run()
    {
        $count = 0;
        while (file_exists($this->lockFile)) {
            if ($count === 0) {
                $message = 'リリースを一時停止します. 再開は `{sudo} navy resume` コマンドを使ってね.';
                $replacements = [
                    '{sudo}' => $this->context->getEnv('sudo'),
                ];
                $message = str_replace(array_keys($replacements), array_values($replacements), $message);

                $this->notifier->notify($message);
            }

            if (++$count > static::LIMIT) {
                $this->notifier->notify('もうずっとリリースが止まりっぱなしです.確認よろしくー @channel');
                $count = 0;
            }

            sleep(static::WAIT);
        }

        if ($count > 0) {
            $this->notifier->notify('リリースを再開しました');
        }
    }
}
