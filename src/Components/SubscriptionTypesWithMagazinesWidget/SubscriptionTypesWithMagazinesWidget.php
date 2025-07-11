<?php

namespace Crm\IssuesModule\Components\SubscriptionTypesWithMagazinesWidget;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\ApplicationModule\UI\Form;
use Crm\IssuesModule\Repositories\MagazinesRepository;
use Crm\IssuesModule\Repositories\SubscriptionTypeMagazinesRepository;
use Crm\SubscriptionsModule\Repositories\SubscriptionTypesRepository;
use Nette\Localization\Translator;
use Tomaj\Form\Renderer\BootstrapRenderer;

/**
 * This widget fetches all magazines for specific subscription type and render list
 * and allows editing and deleting magazines.
 *
 * @package Crm\IssuesModule\Components
 */
class SubscriptionTypesWithMagazinesWidget extends BaseLazyWidget
{
    private $templateName = 'subscription_types_with_magazines_widget.latte';

    private $magazinesRepository;

    private $subscriptionTypesRepository;

    private $subscriptionTypeMagazinesRepository;

    private $translator;

    public function __construct(
        Translator $translator,
        MagazinesRepository $magazinesRepository,
        SubscriptionTypesRepository $subscriptionTypesRepository,
        SubscriptionTypeMagazinesRepository $subscriptionTypeMagazinesRepository,
        LazyWidgetManager $lazyWidgetManager,
    ) {
        parent::__construct($lazyWidgetManager);

        $this->magazinesRepository = $magazinesRepository;
        $this->subscriptionTypesRepository = $subscriptionTypesRepository;
        $this->subscriptionTypeMagazinesRepository = $subscriptionTypeMagazinesRepository;
        $this->translator = $translator;
    }

    public function header()
    {
        return 'Subscription Types With Magazines';
    }

    public function identifier()
    {
        return 'subscriptiontypeswithmagazines';
    }

    public function render($subscriptionType)
    {
        $this->template->type = $subscriptionType;
        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }

    public function createComponentMagazinesForm()
    {
        $form = new Form();
        $form->setRenderer(new BootstrapRenderer());
        $form->setTranslator($this->translator);
        $form->addProtection();
        $form->getElementPrototype()->addAttributes(['class' => 'ajax']);

        if (!isset($this->presenter->params['id'])) {
            $form->addError('issues.components.subscription_types_with_magazines.error.missing_subscription_type_id');
            return $form;
        }

        $subscriptionTypeID = $this->presenter->params['id'];
        $form->addHidden('subscription_type_id', $subscriptionTypeID);

        $form->addGroup();

        $magazines = $this->magazinesRepository->all()->fetchPairs('id', 'name');

        // unset already attached magazines
        $subscriptionType = $this->subscriptionTypesRepository->find($subscriptionTypeID);
        foreach ($subscriptionType->related('subscription_type_magazines') as $pair) {
            unset($magazines[$pair->magazine_id]);
        }

        if (empty($magazines)) {
            $form->addError('issues.components.subscription_types_with_magazines.error.no_magazines_to_add');
        } else {
            $form->addSelect('magazine', 'issues.components.subscription_types_with_magazines.fields.magazine.title', $magazines)
                ->setPrompt('issues.components.subscription_types_with_magazines.fields.magazine.placeholder')
                ->setRequired('issues.components.subscription_types_with_magazines.fields.magazine.required');

            $form->addSubmit('send', 'system.add')
                ->getControlPrototype()
                ->setName('button')
                ->setHtml('<i class="fa fa-save"></i> ' . $this->translator->translate('system.add'));

            $form->onSuccess[] = [$this, 'formSucceeded'];
        }

        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $subscriptionTypeID = $values['subscription_type_id'];
        $magazineID = $values['magazine'];
        $this->subscriptionTypeMagazinesRepository->addSubscriptionTypeMagazine($subscriptionTypeID, $magazineID);

        $magazine = $this->magazinesRepository->find($magazineID);
        $this->getPresenter()->flashMessage($this->translator->translate(
            'issues.components.subscription_types_with_magazines.messages.added',
            ['magazine_name' => $magazine->name],
        ));

        $this->getPresenter()->redirect('SubscriptionTypesAdmin:Show', $subscriptionTypeID);
    }

    public function handleRemoveMagazine($subscriptionTypeID, $magazineID)
    {
        $this->subscriptionTypeMagazinesRepository->removeSubscriptionTypeMagazine($subscriptionTypeID, $magazineID);
        $magazine = $this->magazinesRepository->find($magazineID);
        $this->getPresenter()->flashMessage($this->translator->translate(
            'issues.components.subscription_types_with_magazines.messages.removed',
            ['magazine_name' => $magazine->name],
        ));

        $this->getPresenter()->redirect('SubscriptionTypesAdmin:Show', $subscriptionTypeID);
    }
}
