<?php
namespace Crocos\Plugin\DeployPlugin\ReleaseLog;

class MarkdownScraper
{
    public function scrape($source, $subject)
    {
        $body = '';

        if (preg_match("/## " . preg_quote($subject) . "\r\n(.*?)\n## /s", $source, $matches)) {
            $body = trim($matches[1]);
        }

        return $body;
    }
}
