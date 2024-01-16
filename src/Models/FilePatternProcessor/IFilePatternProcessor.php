<?php

namespace Crm\IssuesModule\Models\FilePatternProcessor;

interface IFilePatternProcessor
{
    public function setDate(\DateTime $dateTime): void;

    public function getFiles(string $folder, ?string $pattern = null): array;
}
