<?php
namespace Crocos\Navy\DeployPlugin\ReleaseLog;

use Navy\GitHub\WebHook\PullRequest;
use Navy\Notifier\NotifierInterface;
use Crocos\Navy\DeployPlugin\Release\CommandContext;

class Notifier
{
    public function __construct(NotifierInterface $notifier, CommandContext $context, MarkdownScraper $scraper, array $config)
    {
        $this->notifier = $notifier;
        $this->context = $context;
        $this->scraper = $scraper;
        $this->config = $config;
    }

    public function notify(PullRequest $pullRequest)
    {
        $body = $pullRequest->getBody();

        $message = $this->createMessage($pullRequest);

        foreach ($this->notifier->getAdapters() as $type => $adapter) {
            $pattern = '/\*\s*\[x\]\s*' . $type . ':\s*(.+)/i';

            if (preg_match_all($pattern, $body, $matches, PREG_SET_ORDER)) {
                $channels = [];
                foreach ($matches as $line) {
                    list($channel, $mentions) = $this->parseChannel($line[1]);
                    $channels[$channel] = $mentions;
                }

                foreach ($channels as $channel => $mentions) {
                    $message = $this->fixMessage($message, $mentions);
                    $adapter->notifyChannel($channel, $message);
                }
            }
        }
    }

    protected function parseChannel($target)
    {
        $mentions = preg_split('/[\s,]+/', $target);

        $channel = array_shift($mentions);

        return [$channel, $mentions];
    }

    protected function fixMessage($body, $mentions = [])
    {
        $target = '';
        if (!empty($mentions)) {
            $target .= " (特に " . implode(' ', $mentions) . " )";
        }

        return $target . " " . trim($body);
    }

    protected function createMessage(PullRequest $pullRequest)
    {
        $title = $pullRequest->getTitle();
        $source = $pullRequest->getBody();
        $url = $pullRequest->getHtmlUrl();

        $labels = $this->createLabelsForLine($pullRequest);

        $message = <<<EOL
間もなくリリース: $labels

$title
=========================================
PR url: $url

EOL;

        foreach ($this->config['topics'] as $subject) {
            $message .= $this->prittyScriping($source, $subject);
        }

        return $message;
    }

    protected function createLabelsForLine(PullRequest $pullRequest)
    {
        $labels = [];

        $labelCollection = $pullRequest->getIssue()->getLabels();
        if (!empty($labelCollection)) {
            $labels = array_map(function ($v) {
                return isset($this->config['labels'][$v]) ? $this->config['labels'][$v] : $v;
            }, iterator_to_array($labelCollection));
        }

        $labels[] = '担当: ' . $this->context->run('username(' . $pullRequest->getMergedUser() . ')');

        return implode(' / ', $labels);
    }

    protected function prittyScriping($source, $subject)
    {
        $result = '';

        $body = $this->scraper->scrape($source, $subject);

        if ($body) {
            $result = <<<EOL

$subject
----------------------------------------
$body

EOL;
        }

        return $result;
    }
}
