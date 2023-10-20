<?php

namespace App\Core;

use Symfony\Component\Validator\Constraints as Assert;

class Config
{
    use IsDataClass;

    #[Assert\NotBlank]
    public string $prefix;

    #[Assert\NotBlank]
    public array $systemAdmins = [];

    #[Assert\NotBlank]
    public array $allowedRoles = [];

    #[Assert\NotBlank]
    public array $allowedUsers = [];

    #[Assert\NotBlank]
    public string $mailCategory;

    #[Assert\NotBlank]
    #[Assert\LessThanOrEqual(0xffffff)]
    #[Assert\GreaterThanOrEqual(0x000000)]
    public int $accentColor = 0x007bff;

    /**
     * @throws \ErrorException
     */
    protected function transformData(array $data): array
    {
        $colorString = $data['accentColor'];

        if (is_string($colorString)) {
            $colorString = '0x' . ($colorString[0] === '#' ? substr($colorString, 1) : $colorString);
            $color = hexdec($colorString);
            $data['accentColor'] = $color;
        }

        return $data;
    }
}