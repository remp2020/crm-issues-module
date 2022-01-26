<?php

namespace Crm\IssuesModule\Repository;

use DateTime;
use Nette\Database\Table\IRow;

class IssueSourceFilesRepository extends IssueBaseRepository
{
    protected $tableName = 'issue_source_files';

    final public function add(IRow $issue, $file, $originalName, $size, $mime)
    {
        $identifier = md5(time() . rand(100000, 99999) . $originalName . $file . $size);
        $id = $this->insert([
            'identifier' => $identifier,
            'issue_id' => $issue->id,
            'file' => $file,
            'original_name' => $originalName,
            'size' => $size,
            'mime' => $mime,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);
        return $this->find($id);
    }

    final public function delete(IRow &$row)
    {
        $this->getMountManager()->delete('issues://' . $row->file);
        return parent::delete($row);
    }

    final public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    final public function getIssueFiles(IRow $issue)
    {
        return $this->getTable()->where('issue_id', $issue->id)->order('original_name ASC');
    }
}
