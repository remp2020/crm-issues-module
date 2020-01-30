<?php

namespace Crm\IssuesModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\ApplicationModule\Widget\WidgetManagerInterface;

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
        $commandsContainer->registerCommand($this->getInstance(\Crm\IssuesModule\Commands\PdfConverterCommand::class));
        $commandsContainer->registerCommand($this->getInstance(\Crm\IssuesModule\Commands\ImportCommand::class));
        $commandsContainer->registerCommand($this->getInstance(\Crm\IssuesModule\Commands\CoverFtpUploadCommand::class));
        $commandsContainer->registerCommand($this->getInstance(\Crm\IssuesModule\Commands\SyncFtpIssuesCommand::class));
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'magazines', 'default'), 'Crm\IssuesModule\Api\MagazinesListingApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'magazine', 'overview'), 'Crm\IssuesModule\Api\MagazineOverviewApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'issues', 'default'), 'Crm\IssuesModule\Api\IssuesListingApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'issues', 'detail'), 'Crm\IssuesModule\Api\IssuesDetailApiHandler', 'Crm\ApiModule\Authorization\NoAuthorization')
        );
    }

    public function registerWidgets(WidgetManagerInterface $widgetManager)
    {
        $widgetManager->registerWidget(
            'subscription_types_admin.show.middle',
            $this->getInstance(\Crm\IssuesModule\Components\SubscriptionTypesWithMagazinesWidget::class),
            100
        );
    }
}
