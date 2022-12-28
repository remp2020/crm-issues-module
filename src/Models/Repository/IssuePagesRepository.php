<?php

namespace Crm\IssuesModule\Repository;

use DateTime;
use Nette\Database\Table\ActiveRow;

class IssuePagesRepository extends IssueBaseRepository
{
    protected $tableName = 'issue_pages';

    final public function add(ActiveRow $issue, $page, $file, $quality, $size, $mime, $width, $height)
    {
        $identifier = md5(time() . rand(99999, 100000) . $file . $size);
        $id = $this->insert([
            'issue_id' => $issue->id,
            'identifier' => $identifier,
            'page' => $page,
            'file' => $file,
            'size' => $size,
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'orientation' => $height > $width ? 'portrait' : 'landscape',
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ]);
        return $this->find($id);
    }

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    final public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    final public function delete(ActiveRow &$row)
    {
        $this->getMountManager()->delete('issues://' . $row->file);
        return parent::delete($row);
    }

    final public function getPages(ActiveRow $issue)
    {
        return $this->getTable()->where('issue_id', $issue->id)->order('page');
    }
}
