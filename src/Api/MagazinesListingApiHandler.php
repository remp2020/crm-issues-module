<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\IssuesModule\Repositories\MagazinesRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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


    public function handle(array $params): ResponseInterface
    {
        $magazines = $this->magazinesRepository->all();

        $magazinesArray = [];
        $total = 0;
        foreach ($magazines as $magazine) {
            $total++;
            $magazinesArray[] = [
                'identifier' => $magazine->identifier,
                'name' => $magazine->name,
                'issues_link' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'default', 'magazine' => $magazine->identifier]),
            ];
        }

        $result = [
            'status' => 'ok',
            'total' => $total,
            'items' => $magazinesArray,
        ];

        $response = new JsonApiResponse(Response::S200_OK, $result);

        return $response;
    }
}
