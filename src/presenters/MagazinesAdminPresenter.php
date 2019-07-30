<?php

namespace Crm\IssuesModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\IssuesModule\Forms\MagazineFormFactory;
use Crm\IssuesModule\Repository\MagazinesRepository;
use Nette\Database\Table\IRow;

class MagazinesAdminPresenter extends AdminPresenter
{
    /** @var  MagazineFormFactory @inject */
    public $magazineFormFactory;

    /** @var  MagazinesRepository @inject */
    public $magazinesRepository;

    public function renderDefault()
    {
        $this->template->magazines = $this->magazinesRepository->all();
    }

    public function renderEdit($id)
    {
        $this->template->magazine = $this->magazinesRepository->find($id);
    }

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
        $this->magazineFormFactory->onCreate = function (IRow $magazine) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_created'));
            $this->redirect('show', $magazine->id);
        };
        $this->magazineFormFactory->onUpdate = function (IRow $magazine) {
            $this->flashMessage($this->translator->translate('issues.admin.issues.messages.issue_updated'));
            $this->redirect('show', $magazine->id);
        };
        return $form;
    }
}
