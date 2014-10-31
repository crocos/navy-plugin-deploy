<?php
namespace Crocos\Plugin\DeployPlugin\ReleaseLog;

use Navy\GitHub\WebHook\PullRequest;

class Logger
{
    protected $period;
    protected $logfile;
    protected $scraper;

    protected $tagPrefix = [
        '*',
        'app:',
        't:',
    ];

    public function __construct(Logfile $logfile, Period $period, MarkdownScraper $scraper)
    {
        $this->logfile = $logfile;
        $this->period = $period;
        $this->scraper = $scraper;
    }

    public function log(PullRequest $pullRequest)
    {
        $title = trim($pullRequest->getTitle());

        $number = $pullRequest->getNumber();
        $owner = $pullRequest->getRepository()->getOwner();
        $name = $pullRequest->getRepository()->getName();

        $labels = '';
        $labelCollection = $pullRequest->getIssue()->getLabels();
        if (!empty($labelCollection)) foreach ($labelCollection as $label) {
            $labels .= '[' . $this->tagMapping($label) .']';
        }

        $user = $pullRequest->getMergedUser();
        $relatedList = $this->fetchRelatedList($pullRequest);

        $log = "* " . $labels . '[' . $user . ']' . $title . PHP_EOL;
        $log .= $relatedList;

        $period = $this->period->getNextPeriod();
        $this->logfile->append($period, $log);
    }

    protected function tagMapping($label)
    {
        $tag = $label;
        foreach ($this->tagPrefix as $prefix) {
            if (false === ($position = stripos($label, $prefix))) continue;

            $tag = str_replace($prefix, '', $label);
            break;
        }

        return $tag;
    }

    protected function fetchRelatedList(PullRequest $pullRequest)
    {
        $result = '';
        $data = trim($this->scraper->scrape($pullRequest->getBody(), '関連URL'));

        if (!empty($data)) {
            $list = explode("\n", $data);
            $indentList = array_map(function ($v) { return '    ' . rtrim($v); }, $list);
            $result = implode(PHP_EOL, $indentList) . PHP_EOL;
        }

        return $result;
    }
}
