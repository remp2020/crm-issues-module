<?php

namespace Crm\IssuesModule\Commands;

use Crm\IssuesModule\Model\IFilePatternProcessor;
use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use League\Flysystem\MountManager;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    private $issuesRepository;

    private $issueSourceFilesRepository;

    private $mountManager;

    private $magazinesRepository;

    private $filePatternProcessor;

    public function __construct(
        MountManager $mountManager,
        IssuesRepository $issuesRepository,
        IssueSourceFilesRepository $issueSourceFilesRepository,
        MagazinesRepository $magazinesRepository,
        IFilePatternProcessor $filePatternProcessor
    ) {
        parent::__construct();
        $this->issuesRepository = $issuesRepository;
        $this->issueSourceFilesRepository = $issueSourceFilesRepository;
        $this->mountManager = $mountManager;
        $this->magazinesRepository = $magazinesRepository;
        $this->filePatternProcessor = $filePatternProcessor;
    }

    protected function configure()
    {
        $this->setName('issues:import')
            ->setDescription('Import issues from disk')
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
                'folder',
                'f',
                InputOption::VALUE_REQUIRED,
                'Folder with files'
            )
            ->addOption(
                'pattern',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Custom pattern for file matching (usable for non-standard issues)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** PDF IMPORT *****</info>');
        $output->writeln('');

        $baseFolder = $input->getOption('folder');
        if (!$baseFolder) {
            throw new \InvalidArgumentException('Specify --folder option');
        }
        if (!is_dir($baseFolder)) {
            throw new \InvalidArgumentException("Folder '$baseFolder'' doesnt exists");
        }
        $magazineId = $input->getOption('magazine');
        if (!$magazineId) {
            throw new \InvalidArgumentException('Specify --magazine option');
        }
        $magazine = $this->magazinesRepository->findByIdentifier($magazineId);
        if (!$magazine) {
            throw new \InvalidArgumentException("Magazine $magazineId not found");
        }
        $output->writeln("Magazine: <comment>{$magazine->name}</comment>");

        $date = DateTime::from(strtotime($input->getArgument('date')));

        $this->filePatternProcessor->setDate($date);
        $files = $this->filePatternProcessor->getFiles($baseFolder, $input->getOption('pattern'));

        if (count($files) == 0) {
            $output->writeln("Cannot find files in <comment>{$baseFolder}</comment> (" . get_class($this->filePatternProcessor) . ")");
            return;
        }

        // pozrieme ci tam este nie su nejake specialy
        // $pattern = "NP_{$patternDate}_NN_[0-9][0-9].pdf";
        // $output->writeln("Pattern: <comment>$pattern</comment>");
        // $specialFiles = Finder::findFiles($pattern)->from($baseFolder);
        // zatial to vypinam - po zmene sruktury, neviem ako to bude chodit este,
        // ked pride prvy tak podla toho sa to bude musiet nastavit
        $specialFiles = [];

        $hashString = '';

        $output->writeln('Found files:');
        foreach ($files as $filePath => $file) {
            $output->writeln(" * <info>{$filePath}</info> ({$file->getSize()}b)");
            $hashString .= "{$filePath}|{$file->getSize()}|";
        }

        $checksum = md5($hashString);

        $actualIssue = $this->issuesRepository->findIssue($magazine, $date);
        if ($actualIssue) {
            if ($actualIssue->checksum == null || $actualIssue->checksum == $checksum) {
                $output->writeln("Issue with date <comment>{$date->format('d.m.Y')}</comment> exists and it is same");
                return;
            } else {
                $output->writeln("Issue with date <comment>{$date->format('d.m.Y')}</comment> exists but it is different");
                $output->writeln("<error>Removing issue {$date->format('d.m.Y')}</error>");
                $this->issuesRepository->deleteIssue($actualIssue);
            }
        }

        // nastavime processing aby sa nahodou nezacalo procesovat kym tam nie su subory
        $issue = $this->issuesRepository->add($magazine, $date, $date->format('d.m.Y'), true, IssuesRepository::STATE_PROCESSING, $checksum);
        $output->writeln("Created issue <comment>#{$issue->id}</comment> - <info>{$issue->name}</info>");

        $output->writeln('Processing files:');
        foreach ($files as $filePath => $file) {
            $output->write(" * <info>$filePath</info>");
            $this->processSourceFile($filePath, $file, $issue);
            $output->write("  <comment>OK</comment>\n");
        }
        foreach ($specialFiles as $filePath => $file) {
            $output->write(" * <info>$filePath</info>");
            $this->processSourceFile($filePath, $file, $issue);
            $output->write("  <comment>OK</comment>\n");
        }

        // nastaviem na new nech sa sprocesuju
        $this->issuesRepository->update($issue, ['state' => IssuesRepository::STATE_NEW]);

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }

    private function processSourceFile($filePath, $file, $issue)
    {
        // WARNING! - tento kod (velmi podovny) je v IssuesFormFactory, ak sa bude menit treba ja tam
        $filename = 'sources/issue-' . str_pad($issue->id, 5, '0', STR_PAD_LEFT) . '/' . md5(time() . $file->getBasename() . $filePath) . '.pdf';
        $this->mountManager->write('issues://' . $filename, file_get_contents($filePath));
        $this->issueSourceFilesRepository->add($issue, $filename, $file->getBasename(), $file->getSize(), 'application/pdf');
    }
}
