<?php

namespace Crm\IssuesModule\Populator;

use Crm\ApplicationModule\Populator\AbstractPopulator;

class MagazinesPopulator extends AbstractPopulator
{
    public function seed($progressBar)
    {
        $magazines = $this->database->table('magazines');

        for ($i = 0; $i < $this->count; $i++) {
            $data = [
                'identifier' => $this->faker->company,
                'name' => $this->faker->sentence,
                'created_at' => $this->faker->dateTimeBetween('-2 years'),
                'updated_at' => $this->faker->dateTimeBetween('-2 years'),
            ];
            $magazines->insert($data);
            $progressBar->advance();
        }
    }
}
