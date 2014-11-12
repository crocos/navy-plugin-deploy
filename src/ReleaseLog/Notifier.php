<?php
namespace Crocos\Navy\DeployPlugin\ReleaseLog;

use Navy\GitHub\WebHook\PullRequest;
use Monolog\Logger as Monolog_Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HipChatHandler;

class Notifier
{
    public function __construct(MarkdownScraper $scraper, $targetRooms, $topics, $hipchatToken, $hipchatName, $labelMapping, $usernameCommand)
    {
        $this->scraper = $scraper;
        $this->targetRooms = $targetRooms;
        $this->topics = $topics;
        $this->hipchatToken = $hipchatToken;
        $this->hipchatName = $hipchatName;
        $this->labelMapping = $labelMapping;
        $this->usernameCommand = $usernameCommand;
    }

    public function notify(PullRequest $pullRequest)
    {
        $body = $pullRequest->getBody();

        $pattern = '/\*\s*\[x\]\s*HipChat:\s*(.*)/i';
        if (preg_match_all($pattern, $body, $matches, PREG_SET_ORDER)) {
            if (empty($matches)) {
                throw new \UnexpectedValueException('empty Release Log Message.');
            }

            $logger = new Monolog_Logger('logger');

            $roomIds = [];
            foreach ($matches as $line) {
                list($key, $mentions) = $this->fetchTarget($line[1]);

                if (array_key_exists($key, $this->targetRooms)) {
                    $roomIds[$this->targetRooms[$key]] = $mentions;
                }
            }

            if (!empty($roomIds)) {
                $message = $this->createMessage($pullRequest);

                $formatter = new LineFormatter('%message%', null, true); // multiline

                foreach ($roomIds as $id => $mentions) {
                    $handler = new HipChatHandler(
                        $this->hipchatToken,
                        $id,
                        $this->hipchatName,
                        true);

                    $handler->setLevel(Monolog_Logger::DEBUG);
                    $handler->setFormatter($formatter);

                    $logger->pushHandler($handler);
                    $logger->debug($this->buildHipChatMessage($message, $mentions));
                    $logger->popHandler();
                }
            }
        }
        #TODO mail send
    }

    /**
     * @return array(roomid, array(mentions))
     */
    protected function fetchTarget($target)
    {
        $target = trim($target);
        if (false === ($position = strpos($target, ' @')) ) {
            return [ $target, [] ];
        }

        $roomId = substr($target, 0, $position);
        $follow = substr($target, $position + 1);

        $mentions = array_filter(explode(' ', trim($follow)));

        return [ trim($roomId), $mentions ];
    }

    protected function buildHipChatMessage($body, $mentions = [])
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

        foreach ($this->topics as $subject) {
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
                return array_key_exists($v, $this->labelMapping) ? $this->labelMapping[$v] : $v;
            }, iterator_to_array($labelCollection));
        }
        $labels[] = '担当:' . $this->detectUsername($pullRequest->getMergedUser());

        return implode(' / ', $labels);
    }

    /**
     * usernameを正規化
     */
    protected function detectUsername($username)
    {
        $command = sprintf($this->usernameCommand, $username);

        return `$command`;
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
