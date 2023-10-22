<?php

namespace App\Mail;

use App\Core\Application;
use Discord\Parts\User\User;
use ErrorException;

class MailBuilder
{
    protected readonly Application $application;
    protected ?User $createdBy = null;
    protected ?string $categoryId = null;
    protected ?User $user = null;

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

    protected function validate(): bool
    {
        return $this->user && $this->categoryId;
    }

    /**
     * @throws ErrorException
     */
    public function create()
    {
        if (!$this->validate()) {
            throw new ErrorException("Data validation failed: could not create mail thread");
        }

        return $this->application->mailService->create([
            'createdBy' => $this->createdBy,
            'user' => $this->user,
            'categoryId' => $this->categoryId,
        ]);
    }
}