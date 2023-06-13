<?php

namespace App\Services\Venmo;

use App\Services\Venmo\Enums\LoginStatus;
use App\Services\Venmo\Objects\VenmoAccount;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Venmo
{
    private PendingRequest $http;

    protected VenmoAccount $venmoAccountObject;

    /**
     * @param string $user_id
     * @param string $password
     * @param string|null $proxy
     */
    public function __construct(
        private string      $user_id,
        private string      $password,
        private string|null $proxy = null
    )
    {
        $this->http = Http::withOptions([
            RequestOptions::COOKIES => new CookieJar(),
            RequestOptions::PROXY => $this->proxy
        ]);

        $this->venmoAccountObject = new VenmoAccount(
            user_id: $this->user_id,
            password: $this->password
        );
    }

    public function handle(): VenmoAccount
    {
        $loginPage = $this
            ->http
            ->get(url: 'https://venmo.com/account/sign-in');

        $loginPageResponseBody = str($loginPage->body());

        $buildId = $loginPageResponseBody->match('/"buildId":"(.*?)",/')->toString();
        $xsrfToken = $loginPageResponseBody->match('/"csrfToken":"(.*?)",/')->toString();

        $postLoginResponse = $this
            ->http
            ->asJson()
            ->withHeaders([
                'csrf-token' => $xsrfToken,
                'Xsrf-Token' => $xsrfToken,
            ])
            ->post(
                url: 'https://venmo.com/api/login',
                data: [
                    'isGroup' => false,
                    'username' => $this->user_id,
                    'password' => $this->password,
                ]
            );

        $postLoginResponseJson = $postLoginResponse->json();
        $postLoginStatusStatus = match ($postLoginResponse->json('code')) {
            264 => LoginStatus::INVALID,
            81109 => LoginStatus::VALID,
            default => LoginStatus::ERROR
        };
        
        if ($postLoginStatusStatus !== LoginStatus::VALID) {
            return $this
                ->venmoAccountObject
                ->setStatus($postLoginStatusStatus)
                ->setMessage($postLoginResponseJson['message'] ?? $postLoginResponseJson['issue'] ?? null);
        }

        $userAuthenticationSecret = $postLoginResponseJson['secret'] ?? null;
        $userPromptJson = $this
            ->http
            ->get(
                url: "https://venmo.com/_next/data/{$buildId}/en/account/mfa/code-prompt.json",
                query: ['k' => $userAuthenticationSecret]
            );

        $userPromptQuestions = $userPromptJson->json('pageProps.questions');
        $userPromptQuestions = collect($userPromptQuestions);

        $userCard = $userPromptQuestions->get(key: 'card');
        $userBank = $userPromptQuestions->get(key: 'bank');

        return $this
            ->venmoAccountObject
            ->setAdditionalData([
                'card' => $userCard,
                'bank' => $userBank,
            ])
            ->setStatus(LoginStatus::VALID)
            ->setMessage('The account is valid.');
    }
}
