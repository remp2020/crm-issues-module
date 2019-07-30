<?php

namespace Crm\IssuesModule\Repository;

use DateTime;
use Nette\Database\Table\IRow;

class IssuePagesRepository extends IssueBaseRepository
{
    protected $tableName = 'issue_pages';

    public function add(IRow $issue, $page, $file, $quality, $size, $mime, $width, $height)
    {
        $identifier = md5(time() . rand(100000, 99999) . $file . $size);
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

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    public function delete(IRow &$row)
    {
        $this->getMountManager()->delete('issues://' . $row->file);
        return parent::delete($row);
    }

    public function getPages(IRow $issue)
    {
        return $this->getTable()->where('issue_id', $issue->id)->order('page');
    }
}
