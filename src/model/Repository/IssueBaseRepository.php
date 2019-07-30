<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Repository;

use League\Flysystem\MountManager;
use Nette\Caching\IStorage;
use Nette\Database\Context;

abstract class IssueBaseRepository extends Repository
{
    /** @var MountManager  */
    private $mountManager;

    public function __construct(Context $database, MountManager $mountManager, IStorage $cacheStorage = null)
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
