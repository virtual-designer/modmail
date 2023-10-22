<?php

namespace App\Mail;

use App\Core\Application;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\User;
use ErrorException;
use Throwable;

class MailBuilder
{
    protected readonly Application $application;
    protected ?User $createdBy = null;
    protected ?string $categoryId = null;
    protected ?User $user = null;
    protected ?Message $message = null;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->categoryId = config()->mailCategory;
    }

    public static function new(): static
    {
        return new static(app());
    }

    public function inCategory(string $id): static
    {
        $this->categoryId = $id;
        return $this;
    }

    public function withUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function withCreatedBy(User $user): static
    {
        $this->createdBy = $user;
        return $this;
    }

    public function withInitialMessage(Message $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function validate(): bool
    {
        return $this->user && $this->categoryId;
    }

    /**
     * @throws ErrorException|Throwable
     */
    public function create(): MailThread
    {
        if (!$this->validate()) {
            throw new ErrorException("Data validation failed: could not create mail thread");
        }

        return $this->application->mailService->create([
            'createdBy' => $this->createdBy,
            'user' => $this->user,
            'categoryId' => $this->categoryId,
            'message' => $this->message,
        ]);
    }

    /**
     * @throws ErrorException|Throwable
     */
    public function fetchOrCreate(): MailThread
    {
        if (!$this->validate()) {
            throw new ErrorException("Data validation failed: could not create mail thread");
        }

        return $this->application->mailService->fetchOrCreate([
            'createdBy' => $this->createdBy,
            'user' => $this->user,
            'categoryId' => $this->categoryId,
            'message' => $this->message,
        ]);
    }
}