<?php

namespace Crm\IssuesModule;

use Crm\ApiModule\Models\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Models\Router\ApiIdentifier;
use Crm\ApiModule\Models\Router\ApiRoute;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\ApplicationModule\Widget\LazyWidgetManagerInterface;
use Crm\IssuesModule\Commands\CoverFtpUploadCommand;
use Crm\IssuesModule\Commands\ImportCommand;
use Crm\IssuesModule\Commands\PdfConverterCommand;
use Crm\IssuesModule\Commands\SyncFtpIssuesCommand;
use Crm\IssuesModule\Components\SubscriptionTypesWithMagazinesWidget\SubscriptionTypesWithMagazinesWidget;

class IssuesModule extends CrmModule
{
    public function registerAdminMenuItems(MenuContainerInterface $menuContainer)
    {
        $mainMenu = new MenuItem('', '#', 'fa fa-copy', 790, true);

        $menuItem1 = new MenuItem(
            $this->translator->translate('issues.menu.issues'),
            ':Issues:IssuesAdmin:default',
            'fa fa-file',
            100,
            true
        );

        $menuItem2 = new MenuItem(
            $this->translator->translate('issues.menu.magazines'),
            ':Issues:MagazinesAdmin:default',
            'fa fa-newspaper',
            200,
            true
        );

        $mainMenu->addChild($menuItem1);
        $mainMenu->addChild($menuItem2);

        $menuContainer->attachMenuItem($mainMenu);
    }

    public function registerCommands(CommandsContainerInterface $commandsContainer)
    {
        $commandsContainer->registerCommand($this->getInstance(PdfConverterCommand::class));
        $commandsContainer->registerCommand($this->getInstance(ImportCommand::class));
        $commandsContainer->registerCommand($this->getInstance(CoverFtpUploadCommand::class));
        $commandsContainer->registerCommand($this->getInstance(SyncFtpIssuesCommand::class));
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'magazines', 'list'), 'Crm\IssuesModule\Api\MagazinesListingApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'magazine', 'overview'), 'Crm\IssuesModule\Api\MagazineOverviewApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'issues', 'list'), 'Crm\IssuesModule\Api\IssuesListingApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'issues', 'detail'), 'Crm\IssuesModule\Api\IssuesDetailApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
    }

    public function registerLazyWidgets(LazyWidgetManagerInterface $widgetManager)
    {
        $widgetManager->registerWidget(
            'subscription_types_admin.show.middle',
            SubscriptionTypesWithMagazinesWidget::class,
            100
        );
    }
}
