<?php

namespace Crm\IssuesModule\Forms;

use Contributte\Translation\Translator;
use Crm\IssuesModule\Repositories\MagazinesRepository;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Tomaj\Form\Renderer\BootstrapRenderer;

class MagazineFormFactory
{
    /** @var Translator */
    private $translator;

    /** @var MagazinesRepository */
    private $magazinesRepository;

    private $magazine;

    /* callback function */
    public $onUpdate;

    /* callback function */
    public $onCreate;

    public function __construct(MagazinesRepository $magazinesRepository, Translator $translator)
    {
        $this->magazinesRepository = $magazinesRepository;
        $this->translator = $translator;
    }

    /**
     * @return Form
     */
    public function create(ActiveRow $magazine = null)
    {
        $this->magazine = $magazine;
        $form = new Form;

        $form->setTranslator($this->translator);
        $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();

        $form->addHidden('id');

        $form->addText('name', 'issues.data.magazines.fields.name')
            ->setHtmlAttribute('placeholder', 'issues.data.magazines.placeholder.name');

        $form->addText('identifier', 'issues.data.magazines.fields.identifier')
            ->setHtmlAttribute('placeholder', 'issues.data.magazines.placeholder.identifier');

        $form->addCheckbox('is_default', 'issues.data.magazines.fields.is_default');

        $form->addSubmit('send', 'system.save');

        if ($magazine) {
            $form->setDefaults($magazine->toArray());
        }

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if ($values->id) {
            $magazine = $this->magazinesRepository->find($values->id);
            unset($values['magazine_id']);
            $this->magazinesRepository->update($magazine, $values);
            $this->onUpdate->__invoke($magazine);
        } else {
            $magazine = $this->magazinesRepository->add($values->identifier, $values->name, $values->is_default);
            $this->onCreate->__invoke($magazine);
        }
    }
}
