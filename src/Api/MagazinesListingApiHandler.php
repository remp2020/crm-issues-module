<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Response\ApiResponseInterface;
use Crm\IssuesModule\Repository\MagazinesRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;

class MagazinesListingApiHandler extends ApiHandler
{
    /** @var MagazinesRepository  */
    private $magazinesRepository;

    public function __construct(MagazinesRepository $magazinesRepository, LinkGenerator $linkGenerator)
    {
        $this->magazinesRepository = $magazinesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params(): array
    {
        return [];
    }


    public function handle(array $params): ApiResponseInterface
    {
        $magazines = $this->magazinesRepository->all();

        $magazinesArray = [];
        $total = 0;
        foreach ($magazines as $magazine) {
            $total++;
            $magazinesArray[] = [
                'identifier' => $magazine->identifier,
                'name' => $magazine->name,
                'issues_link' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'default', 'magazine' => $magazine->identifier]),
            ];
        }

        $result = [
            'status' => 'ok',
            'total' => $total,
            'items' => $magazinesArray,
        ];

        $response = new JsonResponse($result);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
