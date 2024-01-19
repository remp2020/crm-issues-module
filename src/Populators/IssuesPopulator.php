<?php

namespace Crm\IssuesModule\Populators;

use Crm\ApplicationModule\Populators\AbstractPopulator;
use Crm\IssuesModule\Repositories\IssuesRepository;
use Nette\Utils\Random;

class IssuesPopulator extends AbstractPopulator
{
    public function seed($progressBar)
    {
        $issues = $this->database->table('issues');
        $issueSourceFiles = $this->database->table('issue_source_files');
        $issuePages = $this->database->table('issue_pages');

        for ($i = 0; $i < $this->count; $i++) {
            $state = $this->getState();
            $error = null;
            if ($state == IssuesRepository::STATE_ERROR) {
                $error = $this->faker->sentence(6);
            }
            $data = [
                'issued_at' => $this->faker->dateTimeBetween('-2 years'),
                'magazine_id' => $this->getId('magazines'),
                'identifier' => Random::generate(16),
                'name' => $this->faker->sentence,
                'is_published' => $this->faker->boolean(80),
                'state' => $state,
                'error_message' => $error,
                'created_at' => $this->faker->dateTimeBetween('-2 years'),
                'updated_at' => $this->faker->dateTimeBetween('-2 years'),
            ];
            $issue = $issues->insert($data);

            $sourceFiles = random_int(0, 20);
            for ($j = 0; $j < $sourceFiles; $j++) {
                $issueSourceFiles->insert([
                    'identifier' => Random::generate(16),
                    'issue_id' => $issue->id,
                    'file' => $this->faker->uuid . '.' . $this->faker->fileExtension(),
                    'original_name' => $this->faker->slug . '.' . $this->faker->fileExtension(),
                    'size' => random_int(10, 10000),
                    'mime' => $this->faker->mimeType(),
                    'created_at' => $this->faker->dateTimeBetween('-2 years'),
                    'updated_at' => $this->faker->dateTimeBetween('-2 years'),
                ]);
            }

            $pages = random_int(0, 20);
            for ($j = 0; $j < $pages; $j++) {
                $issuePages->insert([
                    'identifier' => Random::generate(16),
                    'issue_id' => $issue->id,
                    'page' => $j,
                    'file' => $this->faker->uuid . '.' . $this->faker->fileExtension(),
                    'size' => random_int(10, 10000),
                    'quality' => 'small',
                    'width' => random_int(100, 2000),
                    'height' => random_int(100, 2000),
                    'mime' => $this->faker->mimeType(),
                    'orientation' => $this->getOrientation(),
                    'created_at' => $this->faker->dateTimeBetween('-2 years'),
                    'updated_at' => $this->faker->dateTimeBetween('-2 years'),
                ]);
                $issuePages->insert([
                    'identifier' => Random::generate(16),
                    'issue_id' => $issue->id,
                    'page' => $j,
                    'file' => $this->faker->uuid . '.' . $this->faker->fileExtension(),
                    'size' => random_int(10, 10000),
                    'quality' => 'large',
                    'width' => random_int(100, 2000),
                    'height' => random_int(100, 2000),
                    'mime' => $this->faker->mimeType(),
                    'orientation' => $this->getOrientation(),
                    'created_at' => $this->faker->dateTimeBetween('-2 years'),
                    'updated_at' => $this->faker->dateTimeBetween('-2 years'),
                ]);
            }

            $progressBar->advance();
        }
    }

    private function getState()
    {
        $items = [
            IssuesRepository::STATE_NEW,
            IssuesRepository::STATE_ERROR,
            IssuesRepository::STATE_OK,
            IssuesRepository::STATE_PROCESSING];
        return $items[array_rand($items)];
    }

    private function getOrientation()
    {
        $items = [
            'portrait',
            'landscape',
        ];
        return $items[array_rand($items)];
    }
}
