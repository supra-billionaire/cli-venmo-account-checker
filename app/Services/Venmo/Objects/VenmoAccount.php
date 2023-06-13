<?php

namespace App\Services\Venmo\Objects;

use App\Services\Venmo\Enums\LoginStatus;

class VenmoAccount
{
    public array $additional_data = [];

    public function __construct(
        public string       $user_id,
        public string       $password,
        public ?LoginStatus $status = null,
        public ?string      $proxy = null,
        public ?string      $message = null,
    )
    {
    }

    public function getAdditionalData(): array
    {
        return $this->additional_data;
    }

    public function setAdditionalData(array $additional_data): self
    {
        $this->additional_data = $additional_data;

        return $this;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function setProxy(?string $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): ?LoginStatus
    {
        return $this->status;
    }

    public function setStatus(?LoginStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function toArray()
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'user_id' => $this->user_id,
                'password' => $this->password,
                'proxy' => $this->proxy,
                'additional_data' => $this->additional_data,
            ],
        ];
    }
}
