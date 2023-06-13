<?php

namespace App\Services\Amex;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
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

    public function handle()
    {
        //        $response = $this->http
        //            ->withHeaders(headers: [
        //                'accept' => '*/*',
        //                'accept-language' => 'en-US,en;q=0.9',
        //                'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1',
        //            ])
        //            ->asForm()
        //            ->post(
        //                url: 'https://global.americanexpress.com/myca/logon/us/action/login',
        //                data: []
        //            );
        //
        //        $responseJson = $response->json();

        $status = Arr::random(['VALID', 'INVALID', 'ERROR']);
        $status = \App\Services\Amex\Enums\LoginStatus::tryFrom($status);

        sleep(rand(3, 7));

        return [
            'status' => $status,
            'message' => $responseJson['message'] ?? null,
            'data' => [
                'user_id' => $this->user_id,
                'password' => $this->password,
                'proxy' => $this->proxy,
            ],
        ];
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
