<?php

namespace Crm\IssuesModule\Repositories;

use DateTime;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Random;

class IssueSourceFilesRepository extends IssueBaseRepository
{
    protected $tableName = 'issue_source_files';

    final public function add(ActiveRow $issue, $file, $originalName, $size, $mime)
    {
        $identifier = Random::generate(16);
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

    final public function delete(ActiveRow &$row)
    {
        $this->getMountManager()->delete('issues://' . $row->file);
        return parent::delete($row);
    }

    final public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    final public function getIssueFiles(ActiveRow $issue)
    {
        return $this->getTable()->where('issue_id', $issue->id)->order('original_name ASC');
    }
}
