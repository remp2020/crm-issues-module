<?php

namespace Crm\IssuesModule\Forms;

use Contributte\Translation\Translator;
use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use League\Flysystem\MountManager;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Tomaj\Form\Renderer\BootstrapRenderer;

class IssuesFormFactory
{
    /** @var  IssuesRepository */
    private $issuesRepository;

    /** @var IssueSourceFilesRepository */
    private $issueSourceFilesRepository;

    /** @var Translator */
    private $translator;

    /** @var MountManager */
    private $mountManager;

    /** @var MagazinesRepository */
    private $magazinesRepository;

    /* callback function */
    public $onUpdate;

    /* callback function */
    public $onCreate;

    private $issue;

    public function __construct(
        IssuesRepository $issuesRepository,
        IssueSourceFilesRepository $issueSourceFilesRepository,
        MagazinesRepository $magazinesRepository,
        Translator $translator,
        MountManager $mountManager
    ) {
        $this->issuesRepository = $issuesRepository;
        $this->issueSourceFilesRepository = $issueSourceFilesRepository;
        $this->magazinesRepository = $magazinesRepository;
        $this->translator = $translator;
        $this->mountManager = $mountManager;
    }

    /**
     * @return Form
     */
    public function create(ActiveRow $issue = null)
    {
        $this->issue = $issue;
        $form = new Form;

        $form->setTranslator($this->translator);
        $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();

        $form->addSelect('magazine_id', 'issues.data.issues.fields.magazine', $this->magazinesRepository->all()->fetchPairs('id', 'name'));

        $form->addText('issued_at', 'issues.data.issues.fields.issued_at')
            ->setRequired('issues.data.issues.required.issued_at')
            ->setHtmlAttribute('placeholder', 'issues.data.issues.placeholder.issued_at');

        $form->addText('name', 'issues.data.issues.fields.name')
            ->setHtmlAttribute('placeholder', 'issues.data.issues.placeholder.name');

        $form->addCheckbox('is_published', 'issues.data.issues.fields.is_published');

        $form->addSelect('state', 'State', [
            IssuesRepository::STATE_NEW => 'new',
            IssuesRepository::STATE_ERROR => 'error',
            IssuesRepository::STATE_OK => 'ok',
            IssuesRepository::STATE_PROCESSING => 'processing',
        ]);

        if (!$issue) {
            $form->addMultiUpload('original_files', 'issues.data.issues.fields.original_files')
                ->setOption('description', 'issues.data.issues.description.original_files');
        }

        $form->addSubmit('send', 'system.save');

        if ($issue) {
            $form->setDefaults($issue->toArray());
        }

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if (isset($values->issued_at)) {
            $values['issued_at'] = DateTime::from(strtotime($values->issued_at));
        }

        if ($this->issue) {
            $values['error_message'] = '';
            $this->issuesRepository->update($this->issue, $values);
            $this->onUpdate->__invoke($this->issue);
        } else {
            $magazine = $this->magazinesRepository->find($values->magazine_id);

            $issue = $this->issuesRepository->add($magazine, $values['issued_at'], $values['name'], $values['is_published'], $values['state']);

            foreach ($values->original_files as $originalFile) {
                if ($originalFile->isOk()) {
                    // WARNING! - tento kod (velmi podobny) je v ImportCommand (IssuesModule), ak sa bude menit treba ja tam
                    $filename = 'sources/issue-' . str_pad($issue->id, 5, '0', STR_PAD_LEFT) . '/' . md5(time() . $originalFile->getName() . $originalFile->getTemporaryFile()) . '.pdf';
                    $this->mountManager->write('issues://' . $filename, $originalFile->getContents());
                    $this->issueSourceFilesRepository->add($issue, $filename, $originalFile->getName(), $originalFile->getSize(), $originalFile->getContentType());
                }
            }

            $this->onCreate->__invoke($issue);
        }
    }
}
