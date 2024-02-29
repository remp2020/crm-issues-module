<?php

namespace Crm\IssuesModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Crm\ApplicationModule\Repositories\AuditLogRepository;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class MagazinesRepository extends Repository
{
    protected $tableName = 'magazines';

    protected $auditLogExcluded = ['updated_at'];

    public function __construct(
        Explorer $database,
        Storage $cacheStorage = null,
        AuditLogRepository $auditLogRepository,
    ) {
        parent::__construct($database, $cacheStorage);
        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * @return Selection
     */
    final public function all()
    {
        return $this->getTable()->order('name ASC');
    }

    final public function exists($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->count('*') > 0;
    }

    final public function add($identifier, $name, $isDefault = false)
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

    final public function defaultMagazine()
    {
        return $this->getTable()->where(['is_default' => true])->limit(1)->fetch();
    }

    final public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }
}
