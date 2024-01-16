<?php

namespace Crm\IssuesModule\Commands;

use Crm\IssuesModule\Models\Pdf\Converter;
use Crm\IssuesModule\Models\Pdf\ConverterError;
use Crm\IssuesModule\Repositories\IssuePagesRepository;
use Crm\IssuesModule\Repositories\IssuesRepository;
use League\Flysystem\MountManager;
use Nette\Utils\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class PdfConverterCommand extends Command
{
    /** @var Converter  */
    private $converter;

    /** @var IssuesRepository  */
    private $issuesRepository;

    /** @var IssuePagesRepository  */
    private $issuePagesRepository;

    /** @var MountManager  */
    private $mountManager;

    public function __construct(Converter $converter, MountManager $mountManager, IssuesRepository $issuesRepository, IssuePagesRepository $issuePagesRepository)
    {
        parent::__construct();
        $this->converter = $converter;
        $this->issuesRepository = $issuesRepository;
        $this->issuePagesRepository = $issuePagesRepository;
        $this->mountManager = $mountManager;
    }

    protected function configure()
    {
        $this->setName('issues:converter')
            ->setDescription('Converts PDF to images. Use flock /tmp/issues_converter.lock to prevent multiple executions.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** PDF CONVERTER *****</info>');
        $output->writeln('');

        $issues = $this->issuesRepository->getIssuesForConverting();
        $pageNumber = 0;
        foreach ($issues as $issue) {
            $output->writeln("Processing issue <info>#{$issue->id}</info>");

            $this->issuesRepository->changeState($issue, IssuesRepository::STATE_PROCESSING);

            $folder = 'generated/' . $issue->issued_at->format('Y/m/') . $issue->identifier;

            $cover = null;

            foreach ($issue->related('issue_source_files')->order("original_name NOT LIKE '%NP%', original_name ASC")->select('issue_source_files.*') as $issueSource) {
                if (!$this->mountManager->has('issues://' . $issueSource->file)) {
                    $this->issuesRepository->setError($issue, "Source file not found '{$issueSource->file}'");
                    continue;
                }

                $output->writeln(' * Generating images');
                $content = $this->mountManager->read('issues://' . $issueSource->file);
                $pdfFile = tempnam(sys_get_temp_dir(), 'issues') . '.pdf';
                $result = file_put_contents($pdfFile, $content);
                if (!$result) {
                    Debugger::log("Cannot write file '{$pdfFile}'", ILogger::EXCEPTION);
                    continue;
                }
                try {
                    $smallImages = $this->converter->generateImages($pdfFile, 0, 1200);
                    $largeImages = $this->converter->generateImages($pdfFile, 0, 2400);

                    if ($cover == null) {
                        $data = $this->converter->generateCover($pdfFile);
                        $file = $data['file'];
                        $cover = $folder . '/cover_' . Random::generate() . '.jpg';
                        $contents = file_get_contents($file);
                        $this->mountManager->write("issues://$cover", $contents);
                        unlink($file);
                    }
                } catch (ConverterError $converterError) {
                    $this->issuesRepository->setError($issue, "Convert error: '{$converterError->getMessage()}'");
                    break;
                } catch (\ErrorException $errorException) {
                    $this->issuesRepository->setError($issue, "Convert error: '{$errorException->getMessage()}'");
                    break;
                }

                if (!$smallImages || !$largeImages) {
                    $this->issuesRepository->setError($issue, 'Converter didn\'t generate required files');
                    break;
                }

                $output->writeln(' * processing pages');

                for ($i = 0; $i < count($smallImages); $i++) {
                    $number = $i;
                    $pageNumber++;

                    $data = $smallImages[$i];
                    $page = $data['file'];
                    $filename = 'small_' . Random::generate() . '.jpg';
                    $contents = file_get_contents($page);
                    $this->mountManager->write("issues://$folder/$filename", $contents);
                    unlink($page);
                    $this->issuePagesRepository->add($issue, $pageNumber, "$folder/$filename", 'small', strlen($contents), $this->converter->getMimeType(), $data['width'], $data['height']);

                    $data = $largeImages[$i];
                    $page = $data['file'];
                    $filename = 'large_' . Random::generate() . '.jpg';
                    $contents = file_get_contents($page);
                    $this->mountManager->write("issues://$folder/$filename", $contents);
                    unlink($page);
                    $this->issuePagesRepository->add($issue, $pageNumber, "$folder/$filename", 'large', strlen($contents), $this->converter->getMimeType(), $data['width'], $data['height']);
                }

                unlink($pdfFile);
            }

            if ($cover) {
                $this->issuesRepository->update($issue, ['cover' => $cover]);
            }

            $this->issuesRepository->changeState($issue, IssuesRepository::STATE_OK);
            $output->writeln('');
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
