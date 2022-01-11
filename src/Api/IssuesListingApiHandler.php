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
use Nette\Http\Response;

class IssuesListingApiHandler extends ApiHandler
{
    /** @var IssuesRepository  */
    private $issuesRepository;

    private $magazinesRepository;

    /** @var LinkGenerator  */
    private $linkGenerator;

    public function __construct(IssuesRepository $issuesRepository, MagazinesRepository $magazinesRepository, LinkGenerator $linkGenerator)
    {
        $this->issuesRepository = $issuesRepository;
        $this->magazinesRepository = $magazinesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params(): array
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'magazine', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_GET, 'year', InputParam::OPTIONAL),
        ];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\Response
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $selectedYear = intval(date('Y'));

        $paramsProcessor = new ParamsProcessor($this->params());
        $params = $paramsProcessor->getValues();
        if (isset($params['year'])) {
            $selectedYear = intval($params['year']);
        }

        $magazine = $this->magazinesRepository->findByIdentifier($params['magazine']);
        if (!$magazine) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Magazine not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $total = $this->issuesRepository->totalPublished($magazine);
        $issues = $this->issuesRepository->yearIssuePublished($magazine, $selectedYear);

        $issuesArray = [];
        $counter = 0;
        foreach ($issues as $issue) {
            $issuesArray[] = [
                'id' => $issue->identifier,
                'issued_at' => $issue->issued_at->format('d.m.Y'),
                'name' => $issue->name,
                'detail' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'detail', 'issue' => $issue->identifier]),
                'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $issue->identifier]),
            ];
            $counter++;
        }

        $years = $this->issuesRepository->availableYears($magazine);
        $yearsArray = [];
        foreach ($years as $year) {
            $yearsArray[] = [
                'year' => $year->year,
                'issues' => $year->count,
                'link' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'default', 'magazine' => $magazine->identifier, 'year' => $year->year])
            ];
        }

        $result = [
            'status' => 'ok',
            'magazine' => $magazine->name,
            'total_issues' => $total,
            'items' => [
                'year' => $selectedYear,
                'issues_in_year' => $counter,
                'issues' => $issuesArray,
            ],
            'years' => $yearsArray,
        ];

        $response = new JsonResponse($result);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
