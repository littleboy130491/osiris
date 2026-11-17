<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateMcpToken extends Command
{
    protected $signature = 'mcp:token {email : Email of the user the token belongs to} {--name=mcp : Token name}';

    protected $description = 'Create a Sanctum API token for connecting an AI agent to the /mcp endpoint';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $token = $user->createToken($this->option('name'), ['mcp'])->plainTextToken;

        $this->info('Token created. It will not be shown again:');
        $this->line($token);
        $this->newLine();
        $this->line('Endpoint: '.url('/mcp'));
        $this->line('Header:   Authorization: Bearer '.$token);

        return self::SUCCESS;
    }
}
