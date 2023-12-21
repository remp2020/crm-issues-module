<?php

namespace Crm\IssuesModule\Presenters;

use Crm\ApplicationModule\Access\AccessManager;
use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\IssuesModule\Model\ContentAccess;
use Crm\IssuesModule\Repository\IssuePagesRepository;
use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use League\Flysystem\MountManager;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\DI\Attributes\Inject;

class DownloadPresenter extends FrontendPresenter
{
    #[Inject]
    public IssueSourceFilesRepository $issueSourceFilesRepository;

    #[Inject]
    public IssuePagesRepository $issuePagesRepository;

    #[Inject]
    public MountManager $mountManager;

    #[Inject]
    public IssuesRepository $issuesRepository;

    #[Inject]
    public AccessManager $accessManager;

    public function renderSourceFile($id)
    {
        // overenie admina
        if (!$this->getUser()->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $user = $this->usersRepository->find($this->getUser()->id);
        if (!$user || UsersRepository::ROLE_ADMIN !== $user->role) {
            throw new ForbiddenRequestException();
        }

        // vypisanie suboru
        $sourceFile = $this->issueSourceFilesRepository->findByIdentifier($id);
        if (!$sourceFile) {
            throw new BadRequestException('File not found');
        }
        $content = $this->mountManager->read('issues://' . $sourceFile->file);

        $httpResponse = $this->getHttpResponse();
        $httpResponse->addHeader('Content-Type', $sourceFile->mime);
        $httpResponse->addHeader('Content-Length', $sourceFile->size);
        $httpResponse->addHeader('Content-Disposition', 'attachment; filename="'.$sourceFile->original_name.'"');
        $this->sendNotCacheHeaders($httpResponse);
        echo $content;
        $this->terminate();
    }

    public function renderPage($id)
    {
        if (!$this->getUser()->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        if (!$this->accessManager->access(ContentAccess::ISSUES)) {
            throw new ForbiddenRequestException();
        }

        $page = $this->issuePagesRepository->findByIdentifier($id);
        if (!$page) {
            throw new BadRequestException('Page not found');
        }

        $httpResponse = $this->getHttpResponse();
        $content = $this->mountManager->read('issues://' . $page->file);
        $httpResponse->addHeader('Content-Type', $page->mime);
        $httpResponse->addHeader('Last-Modified', gmdate('D, d M Y H:i:s ', $page->created_at->getTimestamp()) . 'GMT');
        $httpResponse->addHeader('Content-Length', $page->size);
        $this->sendNotCacheHeaders($httpResponse);
        echo $content;
        $this->terminate();
    }

    public function renderCover($id)
    {
        $issue = $this->issuesRepository->findByIdentifier($id);
        if (!$issue || $issue->state != IssuesRepository::STATE_OK) {
            throw new BadRequestException();
        }

        $httpResponse = $this->getHttpResponse();
        $content = $this->mountManager->read('issues://' . $issue->cover);
        $httpResponse->addHeader('Content-Type', 'image/jpg');
        $httpResponse->addHeader('Last-Modified', gmdate('D, d M Y H:i:s ', $issue->created_at->getTimestamp()) . 'GMT');
        echo $content;
        $this->terminate();
    }

    private function sendNotCacheHeaders($httpResponse)
    {
        $httpResponse->addHeader('Expires', 'Tue, 03 Jul 2001 06:00:00 GMT');
        $httpResponse->addHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $httpResponse->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $httpResponse->addHeader('Cache-Control', 'post-check=0, pre-check=0');
        $httpResponse->addHeader('Pragma', 'no-cache');
        $httpResponse->addHeader('Connection', 'close');
    }
}
