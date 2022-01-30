<?php

namespace Crm\IssuesModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Params\InputParam;
use Crm\ApiModule\Params\ParamsProcessor;
use Crm\IssuesModule\Repository\IssuesRepository;
use Nette\Application\LinkGenerator;
use Nette\Http\Response;

class IssuesDetailApiHandler extends ApiHandler
{
    /** @var IssuesRepository  */
    private $issuesRepository;

    /** @var LinkGenerator  */
    private $linkGenerator;

    public function __construct(IssuesRepository $issuesRepository, LinkGenerator $linkGenerator)
    {
        $this->issuesRepository = $issuesRepository;
        $this->linkGenerator = $linkGenerator;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'issue', InputParam::REQUIRED),
        ];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\Response
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $paramsProcessor = new ParamsProcessor($this->params());
        $params = $paramsProcessor->getValues();
        $issueIdentifier = $params['issue'];

        $issue = $this->issuesRepository->findByIdentifier($issueIdentifier);
        if (!$issue || !$issue->is_published) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Issue not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $pagesArray = [];
        $totalPages = 0;

        foreach ($issue->related('issue_pages')->order('page ASC') as $page) {
            $pagesArray[$page->page][$page->quality] = [
                'id' => $page->identifier,
                'image_url' => $this->linkGenerator->link('Issues:Download:page', ['id' => $page->identifier]),
                'size' => $page->size,
                'width' => $page->width,
                'height' => $page->height,
                'orientation' => $page->orientation,
            ];

            if ($page->quality == 'small') {
                $totalPages++;
            }
        }

        $nextIssueArray = null;
        $prevIssueArray = null;

        $nextIssue = $this->issuesRepository->nextIssue($issue);
        if ($nextIssue) {
            $nextIssueArray = [
                'id' => $nextIssue->identifier,
                'issued_at' => $nextIssue->issued_at->format('d.m.Y'),
                'name' => $nextIssue->name,
                'detail' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'detail', 'issue' => $nextIssue->identifier]),
                'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $nextIssue->identifier]),
            ];
        }
        $prevIssue = $this->issuesRepository->prevIssue($issue);
        if ($prevIssue) {
            $prevIssueArray = [
                'id' => $prevIssue->identifier,
                'issued_at' => $prevIssue->issued_at->format('d.m.Y'),
                'name' => $prevIssue->name,
                'detail' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'issues', 'apiaction' => 'detail', 'issue' => $prevIssue->identifier]),
                'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $prevIssue->identifier]),
            ];
        }

        $result = [
            'status' => 'ok',
            'issue' => [
                'id' => $issue->identifier,
                'issued_at' => $issue->issued_at->format('d.m.Y'),
                'name' => $issue->name,
                'magazine' => [
                    'name' => $issue->magazine->name,
                    'link' => $this->linkGenerator->link('Api:Api:api', ['version' => 1, 'category' => 'magazines', 'apiaction' => 'default', 'magazine' => $issue->magazine->identifier])
                ],
                'cover' => $this->linkGenerator->link('Issues:Download:cover', ['id' => $issue->identifier]),
                'total_pages' => $totalPages,
                'pages' => $pagesArray,
            ],
            'next_issue' => $nextIssueArray,
            'prev_issue' => $prevIssueArray,
        ];

        $response = new JsonResponse($result);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
