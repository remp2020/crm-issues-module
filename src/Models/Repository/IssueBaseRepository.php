<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\ApplicationModule\Repository;
use Nette\Caching\IStorage;
use Nette\Database\Context;

abstract class IssueBaseRepository extends Repository
{
    /** @var ApplicationMountManager  */
    private $mountManager;

    public function __construct(Context $database, ApplicationMountManager $mountManager, IStorage $cacheStorage = null)
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
