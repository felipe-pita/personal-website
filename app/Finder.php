<?php

namespace app;

use SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Finder
{
    public function findFile(string $file, string $searchPath = '/home', $info = true): array
    {
        $fileInfo = new SplFileInfo($file);

        if (!empty($fileInfo->getPath())) {
            $searchPath = $this->findDirectoryPath($fileInfo->getPath(), $searchPath);
            $searchPath = implode(' ', $searchPath);
            $file = $fileInfo->getBaseName();
        }

        $command = $this->getFindCommand($searchPath, $file);

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful() && strlen($process->getErrorOutput()) > 2) {
            throw new ProcessFailedException($process);
        }

        $files = $this->listResult($process->getOutput());

        if ($info) {
            return $this->makeData($files);
        }

        return $files;
    }

    public function createFolder(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0700, true);
        }

        return false;
    }

    public function deleteFolder(string $src): bool
    {
        $dir = opendir($src);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->deleteFolder($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);

        return rmdir($src);
    }

    public function copyFile(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    private function getFindCommand(string $path, string $fileName): array
    {
        return ['find', $path, '-name', $fileName];
    }

    private function findDirectoryPath(string $path, string $searchPath = '/'): array
    {
        $directories = explode('/', $path);

        $directoryName = end($directories);

        $command = array_merge(
            $this->getFindCommand($searchPath, $directoryName),
            ['|', 'grep', $path]
        );

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->listResult($process->getOutput());
    }

    private function listResult(string $output): array
    {
        $dataArray = explode("\n", $output);

        if (empty(end($dataArray))) {
            unset($dataArray[count($dataArray) - 1]);
        }

        return $dataArray;
    }

    private function makeData(array $files): array
    {
        $data = [];

        foreach ($files as $filePath) {
            $fileInfo = new \SplFileInfo($filePath);

            $data[] = [
                'path'       => $fileInfo->getPath(),
                'filename'   => $fileInfo->getFilename(),
                'realpath'   => $fileInfo->getRealpath(),
                'extension'  => $fileInfo->getExtension(),
                'type'       => $fileInfo->getType(),
                'mime_type'  => mime_content_type($filePath),
                'size'       => $fileInfo->getSize(),
                'isFile'     => $fileInfo->isFile(),
                'isDir'      => $fileInfo->isDir(),
                'isLink'     => $fileInfo->isLink(),
                'writable'   => $fileInfo->isWritable(),
                'readable'   => $fileInfo->isReadable(),
                'executable' => $fileInfo->isExecutable(),
            ];
        }

        return $data;
    }
}