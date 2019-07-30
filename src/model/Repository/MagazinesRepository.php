<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class MagazinesRepository extends Repository
{
    protected $tableName = 'magazines';

    /**
     * @return Selection
     */
    public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    public function exists($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->count('*') > 0;
    }

    public function add($identifier, $name, $isDefault = false)
    {
        $id = $this->insert([
            'identifier' => $identifier,
            'name' => $name,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'is_default' => $isDefault,
        ]);
        return $this->find($id);
    }

    public function defaultMagazine()
    {
        return $this->getTable()->where(['is_default' => true])->limit(1)->fetch();
    }

    public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }
}
