<?php

use League\CLImate\CLImate;

require __DIR__ . '/vendor/autoload.php';

Class Publisher {

    public function __construct(
        public readonly CLImate $cli = new CLImate(),
    ) { }

    public function publish(): void
    {
        $this->deletePublicFolder();
        $this->copyAssets();
        $this->convertMarkdownFiles();
    }

    private function deletePublicFolder(): void
    {
        if (rmdir('./public')) {
            $this->cli->out('Public folder successfully removed');
            return;
        }

        $this->cli->error('Public folder could not be removed');
        die();
    }

    private function copyAssets(): void
    {
    }

    private function convertMarkdownFiles(): void
    {
    }
}

(new Publisher())->publish();