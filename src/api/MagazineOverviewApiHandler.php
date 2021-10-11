<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Params\InputParam;
use Crm\ApiModule\Params\ParamsProcessor;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\IRow;
use Nette\Http\Response;

class MagazineOverviewApiHandler extends ApiHandler
{
    /** @var MagazinesRepository */
    private $magazinesRepository;

    /** @var IssuesRepository */
    private $issuesRepository;

    /** @var LinkGenerator */
    private $linkGenerator;

    public function __construct(
        MagazinesRepository $magazinesRepository,
        IssuesRepository $issuesRepository,
        LinkGenerator $linkGenerator
    ) {
        $this->magazinesRepository = $magazinesRepository;
        $this->issuesRepository = $issuesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'magazine', InputParam::REQUIRED),
        ];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\IResponse
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $paramsProcessor = new ParamsProcessor($this->params());
        $params = $paramsProcessor->getValues();

        $magazine = $this->magazinesRepository->findByIdentifier($params['magazine']);
        if (!$magazine) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Magazine not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
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
                    'last_issues' => array_values(array_map(function (IRow $issue) {
                        return [
                            'id' => $issue->identifier,
                            'issued_at' => $issue->issued_at->format('d.m.Y'),
                            'name' => $issue->name,
                            'detail' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'detail', 'issue' => $issue->identifier]),
                            'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $issue->identifier]),
                        ];
                    }, $this->issuesRepository->lastIssues($magazine, $year->year, 6)->fetchAll())),
                ],
                'link' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'default', 'magazine' => $magazine->identifier, 'year' => $year->year])
            ];
        }

        $result = [
            'status' => 'ok',
            'magazine' => [
                'identifier' => $magazine->identifier,
                'name' => $magazine->name,
                'issues_link' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'default', 'magazine' => $magazine->identifier]),
            ],
            'total' => $total,
            'years' => $yearsArray,
        ];

        $response = new JsonResponse($result);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
