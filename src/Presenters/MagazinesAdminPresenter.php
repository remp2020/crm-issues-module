<?php

namespace Crm\IssuesModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\IssuesModule\Forms\MagazineFormFactory;
use Crm\IssuesModule\Repositories\MagazinesRepository;
use Nette\DI\Attributes\Inject;
use Nette\Database\Table\ActiveRow;

class MagazinesAdminPresenter extends AdminPresenter
{
    #[Inject]
    public MagazineFormFactory $magazineFormFactory;

    #[Inject]
    public MagazinesRepository $magazinesRepository;

    /**
     * @admin-access-level read
     */
    public function renderDefault()
    {
        $this->template->magazines = $this->magazinesRepository->all();
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
        $this->template->magazine = $this->magazinesRepository->find($id);
    }

    /**
     * @admin-access-level read
     */
    public function renderShow($id)
    {
        $this->template->magazine = $this->magazinesRepository->find($id);
    }

    protected function createComponentMagazineForm()
    {
        $magazine = null;
        if (isset($this->params['id'])) {
            $magazine = $this->magazinesRepository->find($this->params['id']);
        }
        $form = $this->magazineFormFactory->create($magazine);
        $this->magazineFormFactory->onCreate = function (ActiveRow $magazine) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_created'));
            $this->redirect('show', $magazine->id);
        };
        $this->magazineFormFactory->onUpdate = function (ActiveRow $magazine) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_updated'));
            $this->redirect('show', $magazine->id);
        };
        return $form;
    }
}
