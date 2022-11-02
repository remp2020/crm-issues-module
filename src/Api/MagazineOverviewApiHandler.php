<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Params\InputParam;
use Crm\ApiModule\Params\ParamsProcessor;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Response;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MagazineOverviewApiHandler extends ApiHandler
{
    /** @var MagazinesRepository */
    private $magazinesRepository;

    /** @var IssuesRepository */
    private $issuesRepository;

    public function __construct(
        MagazinesRepository $magazinesRepository,
        IssuesRepository $issuesRepository,
        LinkGenerator $linkGenerator
    ) {
        $this->magazinesRepository = $magazinesRepository;
        $this->issuesRepository = $issuesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params(): array
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'magazine', InputParam::REQUIRED),
        ];
    }


    public function handle(array $params): ResponseInterface
    {
        $paramsProcessor = new ParamsProcessor($this->params());
        $params = $paramsProcessor->getValues();

        $magazine = $this->magazinesRepository->findByIdentifier($params['magazine']);
        if (!$magazine) {
            $response = new JsonApiResponse(Response::S404_NOT_FOUND, ['status' => 'error', 'message' => 'Magazine not found']);
            return $response;
        }

        $total = $this->issuesRepository->totalPublished($magazine);

        $years = $this->issuesRepository->availableYears($magazine);
        $yearsArray = [];
        foreach ($years as $year) {
            $yearsArray[] = [
                'year' => $year->year,
                'issues' => [
                    'count' => $year->count,
                    'last_issues' => array_values(array_map(function (ActiveRow $issue) {
                        return [
                            'id' => $issue->identifier,
                            'issued_at' => $issue->issued_at->format('d.m.Y'),
                            'name' => $issue->name,
                            'detail' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'detail', 'issue' => $issue->identifier]),
                            'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $issue->identifier]),
                        ];
                    }, $this->issuesRepository->lastIssues($magazine, $year->year, 6)->fetchAll())),
                ],
                'link' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'default', 'magazine' => $magazine->identifier, 'year' => $year->year])
            ];
        }

        $result = [
            'status' => 'ok',
            'magazine' => [
                'identifier' => $magazine->identifier,
                'name' => $magazine->name,
                'issues_link' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'default', 'magazine' => $magazine->identifier]),
            ],
            'total' => $total,
            'years' => $yearsArray,
        ];

        $response = new JsonApiResponse(Response::S200_OK, $result);

        return $response;
    }
}
