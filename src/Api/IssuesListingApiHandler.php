<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\IssuesModule\Repositories\IssuesRepository;
use Crm\IssuesModule\Repositories\MagazinesRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;
use Tomaj\NetteApi\Params\GetInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class IssuesListingApiHandler extends ApiHandler
{
    /** @var IssuesRepository  */
    private $issuesRepository;

    private $magazinesRepository;

    public function __construct(IssuesRepository $issuesRepository, MagazinesRepository $magazinesRepository, LinkGenerator $linkGenerator)
    {
        $this->issuesRepository = $issuesRepository;
        $this->magazinesRepository = $magazinesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params(): array
    {
        return [
            (new GetInputParam('magazine'))->setRequired(),
            new GetInputParam('year'),
        ];
    }


    public function handle(array $params): ResponseInterface
    {
        $selectedYear = intval(date('Y'));

        if (isset($params['year'])) {
            $selectedYear = intval($params['year']);
        }

        $magazine = $this->magazinesRepository->findByIdentifier($params['magazine']);
        if (!$magazine) {
            $response = new JsonApiResponse(Response::S404_NOT_FOUND, ['status' => 'error', 'message' => 'Magazine not found']);
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
                'detail' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'detail', 'issue' => $issue->identifier]),
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
                'link' => $this->linkGenerator->link('Api:Api:default', ['version' => 1, 'package' => 'issues', 'apiAction' => 'default', 'magazine' => $magazine->identifier, 'year' => $year->year]),
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

        $response = new JsonApiResponse(Response::S200_OK, $result);

        return $response;
    }
}
