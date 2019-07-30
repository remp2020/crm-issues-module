<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Repository;

class SubscriptionTypeMagazinesRepository extends Repository
{
    protected $tableName = 'subscription_type_magazines';

    public function addSubscriptionTypeMagazine($subscriptionTypeID, $magazineID)
    {
        $id = $this->getTable()->insert([
            'subscription_type_id' => $subscriptionTypeID,
            'magazine_id' => $magazineID,
        ]);
        return $this->getTable()->where(['id' => $id])->fetch();
    }

    public function removeSubscriptionTypeMagazine($subscriptionTypeID, $magazineID)
    {
        return $this->getTable()->where(['subscription_type_id' => $subscriptionTypeID, 'magazine_id' => $magazineID])->delete();
    }
}
