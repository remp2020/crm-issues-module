<?php

namespace Crm\IssuesModule\Commands;

use Crm\ApplicationModule\Models\ApplicationMountManager;
use Crm\IssuesModule\Model\IFilePatternProcessor;
use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use League\Flysystem\FilesystemException;
use Nette\Utils\DateTime;
use Nette\Utils\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class ImportCommand extends Command
{
    public function __construct(
        private ApplicationMountManager $mountManager,
        private IssuesRepository $issuesRepository,
        private IssueSourceFilesRepository $issueSourceFilesRepository,
        private MagazinesRepository $magazinesRepository,
        private IFilePatternProcessor $filePatternProcessor
    ) {
        parent::__construct();
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
            ->addOption(
                'delete-source-after',
                null,
                InputOption::VALUE_NONE,
                'Remove source files after successful import.',
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
            return Command::FAILURE;
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
            $md5Hash = hash_file('sha256', $file->getRealPath());
            $hashString .= "{$filePath}|{$md5Hash}|";
        }

        $checksum = hash('sha256', $hashString);

        $actualIssue = $this->issuesRepository->findIssue($magazine, $date);
        if ($actualIssue) {
            if ($actualIssue->checksum == null || $actualIssue->checksum == $checksum) {
                $output->writeln("Issue with date <comment>{$date->format('d.m.Y')}</comment> exists and it is same");
                return Command::SUCCESS;
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

        if ($input->getOption('delete-source-after')) {
            // processSourceFiles throw exceptions if import wasn't successful
            // so if we are here, it's safe to remove source files
            $output->writeln('Removing source files:');
            foreach ($files as $filePath => $file) {
                $output->write(" * <info>$filePath</info>");
                $result = unlink($filePath);
                if ($result) {
                    $output->write("  <comment>OK</comment>\n");
                } else {
                    $output->write("  <error>Unable to delete file.</error>");
                    Debugger::log("Cannot delete source issue file '{$filePath}'. Please remove it manually.", ILogger::ERROR);
                }
            }
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }

    private function processSourceFile($filePath, $file, $issue)
    {
        // WARNING! - tento kod (velmi podovny) je v IssuesFormFactory, ak sa bude menit treba ja tam
        $filename = 'sources/issue-' . str_pad($issue->id, 5, '0', STR_PAD_LEFT) . '/' . Random::generate() . '.pdf';

        try {
            $this->mountManager->write('issues://' . $filename, file_get_contents($filePath));
        } catch (FilesystemException) {
            throw new \Exception("Unable to import issue file [{$filePath}] into issues file repository.");
        }

        $result = $this->issueSourceFilesRepository->add($issue, $filename, $file->getBasename(), $file->getSize(), 'application/pdf');
        if ($result === null) {
            throw new \Exception("Unable to add entry to issue [{$issue->id}] for file [{$filename}] into 'issue_source_files' table.");
        }
    }
}
