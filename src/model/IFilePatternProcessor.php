<?php

namespace Crm\IssuesModule\Model;

interface IFilePatternProcessor
{
    public function setDate(\DateTime $dateTime): void;

    public function getFiles(string $folder, ?string $pattern = null): array;
}
