<?php

require __DIR__ . '/vendor/autoload.php';

use app\Publisher;

class App
{
    public function __construct(
        public readonly Publisher $publisher = new Publisher(),
    ) { }

    public function publish(): void
    {
        $this->publisher->publish();
    }
}

(new App())->publish();