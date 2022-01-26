<?php

namespace Crm\IssuesModule\Model\Issue;

use Crm\IssuesModule\Model\IFilePatternProcessor;
use Nette\Utils\Finder;

class FilePatternProcessor implements IFilePatternProcessor
{
    // e.g. 20190228_04.pdf
    const PATTERN = "%date%_[0-9][0-9].pdf";

    private $pattern = self::PATTERN;

    public function setDate(\DateTime $date): void
    {
        $this->pattern = str_replace("%date%", $date->format('Ymd'), $this->pattern);
    }

    public function getFiles(string $folder, ?string $pattern = null): array
    {
        $files = Finder::findFiles($this->pattern)->date('>', '- 6 months')->from($folder);
        $result = [];
        foreach ($files as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }
}
