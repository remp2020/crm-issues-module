<?php

namespace Crm\IssuesModule\Commands;

use Crm\IssuesModule\Repository\IssueSourceFilesRepository;
use Crm\IssuesModule\Repository\IssuesRepository;
use Crm\IssuesModule\Repository\MagazinesRepository;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncFtpIssuesCommand extends Command
{
    /** @var IssuesRepository  */
    private $issuesRepository;

    /** @var IssueSourceFilesRepository  */
    private $issueSourceFilesRepository;

    /** @var MountManager  */
    private $mountManager;

    /** @var MagazinesRepository  */
    private $magazinesRepository;

    public function __construct(MountManager $mountManager, IssuesRepository $issuesRepository, IssueSourceFilesRepository $issueSourceFilesRepository, MagazinesRepository $magazinesRepository)
    {
        parent::__construct();
        $this->issuesRepository = $issuesRepository;
        $this->issueSourceFilesRepository = $issueSourceFilesRepository;
        $this->mountManager = $mountManager;
        $this->magazinesRepository = $magazinesRepository;
    }

    protected function configure()
    {
        $this->setName('issues:ftp-import')
            ->setDescription('Import ftp issues from disk')
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp host'
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp username'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp password'
            )
            ->addOption(
                'local-folder',
                null,
                InputOption::VALUE_REQUIRED,
                'Local folder'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Ftp root path'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** SYNC FTP IMPORT *****</info>');
        $output->writeln('');

        $ftpAdapter = new Ftp([
            'host' => $input->getOption('host'),
            'username' => $input->getOption('username'),
            'password' => $input->getOption('password'),
            'root' => $input->getOption('path')
        ]);
        $localAdapter = new Local($input->getOption('local-folder'));

        $ftp = new Filesystem($ftpAdapter);
        $local = new Filesystem($localAdapter);

        $manager = new MountManager([
            'ftp' => $ftp,
            'local' => $local,
        ]);

        $contents = $manager->listContents('ftp://', true);

        foreach ($contents as $entry) {
            if ($entry['type'] !== 'file') {
                continue;
            }

            $update = true;

//            if (!$manager->has('local://' . $entry['path'])) {
//                $update = true;
//            } elseif ($manager->getTimestamp('ftp://' . $entry['path']) > $manager->getTimestamp('local://' . $entry['path'])) {
//                $update = true;
//            }

            if ($update) {
                $output->writeln('Downloading <info>' . $entry['path'] . '</info>');
                try {
                    $manager->put('local://' . $entry['path'], $manager->read('ftp://' . $entry['path']));
                } catch (FileNotFoundException $e) {
                    $output->writeln("Cannot download <error>{$entry['path']}</error>");
                    continue;
                }
            }
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return 0;
    }
}
