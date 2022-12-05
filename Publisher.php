<?php

use JetBrains\PhpStorm\NoReturn;
use League\CLImate\CLImate;
use MehrdadDadkhah\File\Finder;

require __DIR__ . '/vendor/autoload.php';

Class Publisher {
    private readonly string $publicFolder;
    private readonly array $contentFolders;

    public function __construct(
        public readonly CLImate $cli = new CLImate(),
        public readonly Finder $finder = new Finder(),
        public readonly Parsedown $parsedown = new Parsedown(),
    ) {
        $this->publicFolder = './public';
        $this->contentFolders = ['pages', 'posts'];
    }

    public function publish(): void
    {
        $this->deletePublicFolder();
        $this->copyAssets();
        $this->convertMarkdownFiles();
    }

    private function deletePublicFolder(): void
    {
        $this->cli->border('-', 100);
        $this->cli->out('Deleting public folder');

        $folderExists = is_dir($this->publicFolder);

        if (!$folderExists) {
            $this->cli->error('Public folder does not exists');
            return;
        }

        $this->removeFolder($this->publicFolder);

        $this->cli->out('Public folder successfully removed');
    }

    private function copyAssets(): void
    {
        $this->cli->border('-', 100);
        $this->cli->out('Copying assets');

        foreach ($this->contentFolders as $folder) {
            foreach ($this->finder->findFile('*.*', $folder) as $file) {
                if (!str_ends_with($file['filename'], '.md')) {
                    $this->createFolder($file['path']);
                    $this->copyFile($file['path'], $file['filename']);
                }
            }
        }
    }

    private function removeFolder(string $src): void
    {
        $dir = opendir($src);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->removeFolder($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);

        rmdir($src);
    }

    private function createFolder(string $path): void
    {
        $newPath = $this->publicFolder . '/' . $path;

        if (!is_dir($newPath)) {
            mkdir($newPath, 0700, true);
        }
    }

    private function copyFile(string $path, string $file): void
    {
        $from = $path . '/' . $file;
        $to = $this->publicFolder . '/' . $path . '/' . $file;
        copy($from, $to);
        $this->cli->red($from);
        $this->cli->green($to);
    }

    private function convertMarkdownFiles(): void
    {
        $this->cli->border('-', 100);
        $this->cli->out('Converting md files');

        foreach ($this->contentFolders as $folder) {
            foreach ($this->finder->findFile('*.md', $folder) as $file) {
                $this->createFolder($file['path']);

                $markdownFilePath = './' . $file['path'] . '/' . $file['filename'];
                $this->cli->red($markdownFilePath);

                $markdownFileContents = file_get_contents($markdownFilePath);
                $markdownToHtml = $this->parsedown->parse($markdownFileContents);

                $htmlFileName = str_replace('.md', '.html', $file['filename']);
                $htmlFilePath = $this->publicFolder . '/' . $file['path'] . '/' . $htmlFileName;

                $htmlFile = fopen($htmlFilePath, "w");
                fwrite($htmlFile, $markdownToHtml);
                fclose($htmlFile);

                $this->cli->green($htmlFilePath);
            }
        }
    }

    #[NoReturn] private function dd($var): void
    {
        die(print_r($var));
    }
}

(new Publisher())->publish();