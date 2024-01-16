<?php

namespace Crm\IssuesModule\Repository;

use Crm\ApplicationModule\Cache\CacheRepository;
use Crm\ApplicationModule\Models\ApplicationMountManager;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

class IssuesRepository extends IssueBaseRepository
{
    protected $tableName = 'issues';

    const STATE_NEW = 'new';
    const STATE_ERROR = 'error';
    const STATE_OK = 'ok';
    const STATE_PROCESSING = 'processing';

    /** @var IssueSourceFilesRepository  */
    private $issueSourceFilesRepository;

    /** @var IssuePagesRepository  */
    private $issuePagesRepository;

    /** @var CacheRepository */
    private $cacheRepository;

    public function __construct(
        Explorer $database,
        ApplicationMountManager $mountManager,
        IssueSourceFilesRepository $issueSourceFilesRepository,
        IssuePagesRepository $issuePagesRepository,
        CacheRepository $cacheRepository
    ) {
        parent::__construct($database, $mountManager);
        $this->issueSourceFilesRepository = $issueSourceFilesRepository;
        $this->issuePagesRepository = $issuePagesRepository;
        $this->cacheRepository = $cacheRepository;
    }

    final public function add(
        ActiveRow $magazine,
        DateTime $issuedAt,
        $name,
        $isPublished = true,
        $state = self::STATE_NEW,
        $checksum = null
    ) {
        $identifier = Random::generate(16);
        $id = $this->insert([
            'magazine_id' => $magazine->id,
            'identifier' => $identifier,
            'issued_at' => $issuedAt,
            'state' => $state,
            'name' => $name,
            'is_published' => $isPublished,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'checksum' => $checksum,
        ]);
        return $this->find($id);
    }

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    final public function setCover(ActiveRow $issue, $cover)
    {
        return $this->update($issue, ['cover' => $cover]);
    }

    final public function getPublicIssues(DateTime $fromTime = null, DateTime $endTime = null)
    {
        $where = ['is_published' => true];
        if ($fromTime) {
            $where['issued_at >= ?'] = $fromTime;
        }
        if ($endTime) {
            $where['issued_at <= ?'] = $endTime;
        }
        $where['state'] = self::STATE_OK;
        return $this->getTable()->where($where)->order('issued_at DESC');
    }

    final public function deleteIssue(ActiveRow &$row)
    {
        foreach ($row->related('issue_source_files') as $sourceFile) {
            $this->issueSourceFilesRepository->delete($sourceFile);
        }
        foreach ($row->related('issue_pages') as $page) {
            $this->issuePagesRepository->delete($page);
        }
        if ($row->cover) {
            $this->getMountManager()->delete('issues://' . $row->cover);
        }
        return parent::delete($row);
    }

    final public function getIssues(ActiveRow $magazine = null)
    {
        $where = [];
        if ($magazine) {
            $where['magazine_id'] = $magazine->id;
        }
        return $this->getTable()->where($where)->order('issued_at DESC');
    }

    final public function getIssuesForConverting()
    {
        return $this->getTable()->where(['state' => [self::STATE_NEW]])->select('issues.*')->order('created_at ASC');
    }

    final public function changeState(ActiveRow $issue, $state)
    {
        return parent::update($issue, [
            'updated_at' => new DateTime(),
            'state' => $state,
        ]);
    }

    final public function setError(ActiveRow $issue, $error)
    {
        return parent::update($issue, [
            'updated_at' => new DateTime(),
            'state' => self::STATE_ERROR,
            'error_message' => $error,
        ]);
    }

    final public function totalCount($allowCached = false, $forceCacheUpdate = false): int
    {
        $callable = function () {
            return parent::totalCount();
        };
        if ($allowCached) {
            return (int) $this->cacheRepository->loadAndUpdate(
                'issues_count',
                $callable,
                DateTime::from(CacheRepository::REFRESH_TIME_5_MINUTES),
                $forceCacheUpdate
            );
        }
        return $callable();
    }

    final public function totalPublished(ActiveRow $magazine)
    {
        return $this->getTable()->where(['is_published' => true, 'state' => self::STATE_OK, 'magazine_id' => $magazine->id])->count('*');
    }

    final public function yearIssuePublished(ActiveRow $magazine, $year = false)
    {
        if (!$year) {
            $year = date('Y');
        }
        $start = DateTime::from(strtotime("1.1.$year 00:00"));
        $end = $start->modifyClone('next year');
        return $this->getTable()->where(['is_published' => true, 'state' => self::STATE_OK, 'magazine_id' => $magazine->id, 'issued_at >= ?' => $start, 'issued_at < ?' => $end])->order('issued_at DESC');
    }

    final public function availableYears(ActiveRow $magazine)
    {
        return $this->getTable()->select('YEAR(issued_at) AS year, COUNT(id) AS count')->where(['is_published' => true, 'state' => self::STATE_OK, 'magazine_id' => $magazine->id])->group('YEAR(issued_at)')->order('year DESC');
    }

    final public function findByIdentifier($identifier)
    {
        return $this->getTable()->where(['identifier' => $identifier])->limit(1)->fetch();
    }

    final public function nextIssue(ActiveRow $issue)
    {
        return $this->getTable()->where(['magazine_id' => $issue->magazine_id, 'state' => self::STATE_OK, 'issued_at > ' => $issue->issued_at])->order('issued_at ASC')->limit(1)->fetch();
    }

    final public function prevIssue(ActiveRow $issue)
    {
        return $this->getTable()->where(['magazine_id' => $issue->magazine_id, 'state' => self::STATE_OK, 'issued_at < ' => $issue->issued_at])->order('issued_at DESC')->limit(1)->fetch();
    }

    final public function lastIssues(ActiveRow $magazine, $year, $limit = 5)
    {
        return $this->getTable()->where([
            'magazine_id' => $magazine->id,
            'state' => self::STATE_OK,
            'issued_at <= ' => DateTime::from(strtotime('31.12.' . $year . '23:59:59')),
            'issued_at >= ' => DateTime::from(strtotime('01.01.' . $year . '00:00:00'))
        ])->order('issued_at DESC')->limit($limit);
    }

    final public function totalDiskSpace(ActiveRow $issue)
    {
        return $issue->related('issue_source_files')->sum('size') + $issue->related('issue_pages')->sum('size');
    }

    final public function exists(ActiveRow $magazine, DateTime $date)
    {
        return $this->getTable()->where(['magazine_id' => $magazine->id, 'issued_at' => $date])->count('*');
    }

    final public function findIssue(ActiveRow $magazine, DateTime $date)
    {
        return $this->getTable()->where(['magazine_id' => $magazine->id, 'issued_at' => $date])->fetch();
    }
}
