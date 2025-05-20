<?php

namespace Crm\IssuesModule\Commands;

use Crm\ApplicationModule\Commands\DecoratedCommandTrait;
use ErrorException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class SyncFtpIssuesCommand extends Command
{
    use DecoratedCommandTrait;

    private const MAX_RETRY_COUNT = 3;

    protected function configure()
    {
        $this->setName('issues:ftp-import')
            ->setDescription('Import ftp issues from disk')
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp host',
            )
            ->addOption(
                'username',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp username',
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'Ftp password',
            )
            ->addOption(
                'local-folder',
                null,
                InputOption::VALUE_REQUIRED,
                'Local folder',
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Ftp root path',
                '',
            )
            ->addOption(
                'delete-remote-after',
                null,
                InputOption::VALUE_NONE,
                'Delete downloaded files from remote after download finished',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ftpAdapter = new FtpAdapter(new FtpConnectionOptions(
            host: $input->getOption('host'),
            root: $input->getOption('path'),
            username: $input->getOption('username'),
            password: $input->getOption('password'),
        ));

        $localAdapter = new LocalFilesystemAdapter($input->getOption('local-folder'));

        $ftp = new Filesystem($ftpAdapter);
        $local = new Filesystem($localAdapter);

        $manager = new MountManager([
            'ftp' => $ftp,
            'local' => $local,
        ]);

        $contents = $manager->listContents('ftp://')->toArray();
        $output->writeln(' * Found <comment>' . count($contents) . '</comment> files/directories.');

        foreach ($contents as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            $fileRemotePath = $entry->path();
            $filename = str_replace('ftp://', '', $fileRemotePath);
            $fileLocalPath = 'local://' . $filename;

            $output->writeln("Found file <info>{$filename}</info>:");

            if ($manager->has($fileLocalPath) === true) {
                $output->writeln(' * Already downloaded.');

                // file is downloaded; delete from remote if flag is set
                if ($input->getOption('delete-remote-after')) {
                    $this->deleteRemoteFile($manager, $fileRemotePath, $output);
                }
                continue;
            }

            $retryCount = 0;

            while (true) {
                $output->writeln('Downloading <info>' . $filename . '</info>');
                try {
                    $manager->write($fileLocalPath, $manager->read($fileRemotePath));
                    $output->writeln(' * File downloaded.');

                    // file is downloaded; delete from remote if flag is set
                    if ($input->getOption('delete-remote-after')) {
                        $this->deleteRemoteFile($manager, $fileRemotePath, $output);
                    }
                    break;
                } catch (FilesystemException | ErrorException $exception) {
                    if ($retryCount >= self::MAX_RETRY_COUNT) {
                        $errorMsg = "Cannot sync file '{$filename}' due exception: {$exception->getMessage()}";
                        Debugger::log($errorMsg, ILogger::EXCEPTION);
                        $output->writeln(' * ' . $errorMsg);
                        break;
                    }

                    $retryCount++;
                    $output->writeln(" * Retry to download <error>{$filename}</error>, try: {$retryCount}");
                    sleep(5);
                }
            }
        }

        $output->writeln('Done');
        return Command::SUCCESS;
    }

    private function deleteRemoteFile(MountManager $mountManager, string $remotePath, OutputInterface $output)
    {
        try {
            $mountManager->delete($remotePath);
            $output->writeln(' * Remote file deleted.');
        } catch (FilesystemException $e) {
            $output->writeln(' * Unable to delete remote file.');
            Debugger::log("Cannot delete remote file '{$remotePath}'", ILogger::ERROR);
        }
    }
}
