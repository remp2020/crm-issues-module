<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\ApplicationModule\Repository;
use Nette\Caching\Storage;
use Nette\Database\Explorer;

abstract class IssueBaseRepository extends Repository
{
    /** @var ApplicationMountManager  */
    private $mountManager;

    public function __construct(Explorer $database, ApplicationMountManager $mountManager, Storage $cacheStorage = null)
    {
        parent::__construct($database, $cacheStorage);
        $this->database = $database;
        $this->mountManager = $mountManager;
    }

    protected function getMountManager()
    {
        return $this->mountManager;
    }
}
