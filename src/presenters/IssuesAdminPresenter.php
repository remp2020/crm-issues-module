<?php

namespace Crm\IssuesModule\Presenters;

use Crm\ApplicationModule\Components\VisualPaginator;
use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\IssuesModule\Forms\IssuesFormFactory;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\IRow;
use Tomaj\Form\Renderer\BootstrapInlineRenderer;

class IssuesAdminPresenter extends AdminPresenter
{
    /** @var  IssuesFormFactory @inject */
    public $issuesFormFactory;

    /** @var  IssuesRepository @inject */
    public $issuesRepository;

    /** @var  MagazinesRepository @inject */
    public $magazineRepository;

    /** @var  @persistent */
    public $magazine;

    /**
     * @admin-access-level read
     */
    public function renderDefault()
    {
        $magazine = $this->magazineRepository->find($this->magazine);
        $issues = $this->issuesRepository->getIssues($magazine ? $magazine : null);

        $vp = new VisualPaginator();
        $this->addComponent($vp, 'vp');
        $paginator = $vp->getPaginator();
        $paginator->setItemCount($issues->count('*'));
        $paginator->setItemsPerPage($this->onPage);
        $this->template->vp = $vp;

        $this->template->issues = $issues->limit($paginator->getLength(), $paginator->getOffset());
        $this->template->totalIssues = $this->issuesRepository->totalCount();
    }

    /**
     * @admin-access-level read
     */
    public function renderShow($id)
    {
        $issue = $this->issuesRepository->find($id);
        if (!$issue) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_not_found', ['issue_id' => $id]), 'danger');
            $this->redirect('default');
        }

        $this->template->issue = $issue;
        $this->template->totalDiskSpace = $this->issuesRepository->totalDiskSpace($issue);
    }

    /**
     * @admin-access-level write
     */
    public function renderNew()
    {
    }

    /**
     * @admin-access-level write
     */
    public function renderEdit($id)
    {
        $this->template->issue = $this->issuesRepository->find($id);
    }

    /**
     * @admin-access-level write
     */
    public function handleDelete($id)
    {
        $issue = $this->issuesRepository->find($id);
        $this->issuesRepository->deleteIssue($issue);
        $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_deleted'));
        $this->redirect('default');
    }

    protected function createComponentIssueForm()
    {
        $issue = null;
        if (isset($this->params['id'])) {
            $issue = $this->issuesRepository->find($this->params['id']);
        }
        $form = $this->issuesFormFactory->create($issue);
        $this->issuesFormFactory->onCreate = function (IRow $issue) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_created'));
            $this->redirect('show', $issue->id);
        };
        $this->issuesFormFactory->onUpdate = function (IRow $issue) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_updated'));
            $this->redirect('show', $issue->id);
        };
        return $form;
    }

    protected function createComponentAdminFilterForm()
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        $form->setRenderer(new BootstrapInlineRenderer());
        $form->addSelect('magazine', '', $this->magazineRepository->all()->fetchPairs('id', 'name'))
            ->setPrompt('--');
        $form->addSubmit('send', 'mail.admin.test_email.send')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-filter"></i> ' . $this->translator->translate('system.filter'));

        $form->addSubmit('cancel', 'system.cancel_filter')->onClick[] = function () {
            $this->redirect('default', ['magazine' => '']);
        };
        $form->onSuccess[] = function (Form $form, $values) {
            $this->redirect('default', [
                'magazine' => $values['magazine'],
            ]);
        };
        $form->setDefaults([
            'magazine' => isset($_GET['magazine']) ? $_GET['magazine'] : null,
        ]);
        return $form;
    }
}
