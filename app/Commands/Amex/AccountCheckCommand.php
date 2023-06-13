<?php

namespace App\Commands\Amex;

use LaravelZero\Framework\Commands\Command;

class AccountCheckCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'amex:check 
        {--list=list.txt : List of accounts to check} 
        {--proxy= : Proxy to use} 
        {--threads=10 : Number of threads to use}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Check mass Amex accounts. Check if account is a valid Amex account.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
    }
}
