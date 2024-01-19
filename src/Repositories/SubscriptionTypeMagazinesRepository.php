<?php

namespace Crm\IssuesModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;

class SubscriptionTypeMagazinesRepository extends Repository
{
    protected $tableName = 'subscription_type_magazines';

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
