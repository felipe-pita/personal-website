<?php

namespace app;

use League\CLImate\CLImate;
use Parsedown;

class Publisher
{
    private readonly string $publicFolder;
    private readonly array $contentFolders;

    public function __construct(
        public readonly CLImate $cli = new CLImate(),
        public readonly Finder $finder = new Finder(),
        public readonly Parsedown $parsedown = new Parsedown(),
    ) {
        $this->publicFolder = './docs';
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
        $this->cli->out('Deleting docs folder');

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



    private function convertMarkdownFiles(): void
    {
        $this->cli->border('-', 100);
        $this->cli->out('Converting md files');

        foreach ($this->contentFolders as $folder) {
            foreach ($this->finder->findFile('*.md', $folder) as $file) {
                $this->createFolder($file['path']);

                $markdownFilePath = './' . $file['path'] . '/' . $file['filename'];
                $this->cli->red($markdownFilePath);

                $markdownFileContents = $this->parseIncludes($markdownFilePath);

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

    private function parseIncludes(string $filePath): string
    {
        return file_get_contents($filePath);
    }
}