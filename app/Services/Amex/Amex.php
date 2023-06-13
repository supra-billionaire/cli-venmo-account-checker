<?php

namespace App\Services\Amex;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;

class Amex
{
    private \Illuminate\Http\Client\PendingRequest $http;

    /**
     * @param  string|null  $proxy
     */
    public function __construct(
        private string $user_id,
        private string $password,
        private string|null $proxy = null
    ) {
        $this->http = Http::withOptions([
            RequestOptions::PROXY => $this->proxy,
        ]);
    }

    public function handle(): void
    {
        //
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }
}
