<?php

namespace Crm\IssuesModule\Commands;

use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use League\Flysystem\UnableToReadFile;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoverFtpUploadCommand extends Command
{
    /** @var IssuesRepository  */
    private $issuesRepository;

    /** @var ApplicationMountManager  */
    private $mountManager;

    /** @var MagazinesRepository  */
    private $magazinesRepository;

    /** @var IssueSourceFilesRepository  */
    private $issueSourceFilesRepository;

    public function __construct(
        ApplicationMountManager $mountManager,
        IssuesRepository $issuesRepository,
        MagazinesRepository $magazinesRepository,
        IssueSourceFilesRepository $issueSourceFilesRepository
    ) {
        parent::__construct();
        $this->issuesRepository = $issuesRepository;
        $this->mountManager = $mountManager;
        $this->magazinesRepository = $magazinesRepository;
        $this->issueSourceFilesRepository = $issueSourceFilesRepository;
    }

    protected function configure()
    {
        $this->setName('issues:cover_ftp_upload')
            ->setDescription('Upload covers from issues to ftp')
            ->addArgument(
                'date',
                InputArgument::REQUIRED,
                'What day do you want to import'
            )
            ->addOption(
                'magazine',
                'm',
                InputOption::VALUE_REQUIRED,
                'Magazine identifier'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'File name format'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** PDF COVER FTP UPLOAD *****</info>');
        $output->writeln('');

        $magazineId = $input->getOption('magazine');
        if (!$magazineId) {
            throw new \InvalidArgumentException('Specify --magazine option');
        }
        $format = $input->getOption('format');
        if (!$format) {
            throw new \InvalidArgumentException('Specify --format option - example SLK_DEN_*date1*.pdf');
        }

        $magazine = $this->magazinesRepository->findByIdentifier($magazineId);
        if (!$magazine) {
            throw new \InvalidArgumentException("Magazine $magazineId not found");
        }
        $output->writeln("Magazine: <comment>{$magazine->name}</comment>");

        $date = DateTime::from(strtotime($input->getArgument('date')));

        $issue = $this->issuesRepository->getIssues($magazine)->where(['issued_at' => $date, 'state' => IssuesRepository::STATE_OK])->fetch();
        if (!$issue) {
            $output->writeln("Issue with date <comment>{$date->format('d.m.Y')}</comment> doesn't exists");
            return Command::FAILURE;
        }

        $fileName = str_replace('*date1*', $issue->issued_at->format('Ymd'), $format);

        try {
            $this->mountManager->read('newsmuseum://' . $fileName);
            $output->writeln("Issue file {$fileName} already exists");
        } catch (UnableToReadFile $e) {
            $files = $this->issueSourceFilesRepository->getIssueFiles($issue);
            $cover = null;
            foreach ($files as $file) {
                $cover = $file;
                break;
            }

            if ($cover) {
                $this->mountManager->copy('issues://' . $cover->file, 'newsmuseum://' . $fileName);
                $f = fopen('/tmp/newseum.log', 'a+');
                fwrite($f, "Copied {$fileName}");
                fclose($f);
                $output->writeln('Issue cover was successfully copied');
            } else {
                $output->writeln('Issue cover not found');
            }
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
