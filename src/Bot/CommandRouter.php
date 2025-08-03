<?php

namespace Bot;

use Bot\Commands\StartCommand;

class CommandRouter
{
    protected array $commands = [
        '/start' => StartCommand::class,
    ];

    public function route(string $command_text)
    {
        $command_name = explode(' ', $command_text)[0];

        if (isset($this->commands[$command_name])) {
            $command_class = $this->commands[$command_name];
            return new $command_class();
        }

        return null;
    }
}
