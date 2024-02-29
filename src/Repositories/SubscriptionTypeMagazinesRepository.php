<?php

namespace Crm\IssuesModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Crm\ApplicationModule\Repositories\AuditLogRepository;
use Nette\Caching\Storage;
use Nette\Database\Explorer;

class SubscriptionTypeMagazinesRepository extends Repository
{
    protected $tableName = 'subscription_type_magazines';

    public function __construct(
        Explorer $database,
        Storage $cacheStorage = null,
        AuditLogRepository $auditLogRepository,
    ) {
        parent::__construct($database, $cacheStorage);
        $this->auditLogRepository = $auditLogRepository;
    }

    final public function addSubscriptionTypeMagazine($subscriptionTypeID, $magazineID)
    {
        $record = $this->getTable()->where([
            'subscription_type_id' => $subscriptionTypeID,
            'magazine_id' => $magazineID,
        ])->fetch();

        if (!$record) {
            $this->getTable()->insert([
                'subscription_type_id' => $subscriptionTypeID,
                'magazine_id' => $magazineID,
            ]);
        }

        return $record;
    }

    final public function removeSubscriptionTypeMagazine($subscriptionTypeID, $magazineID)
    {
        return $this->getTable()->where(['subscription_type_id' => $subscriptionTypeID, 'magazine_id' => $magazineID])->delete();
    }
}
