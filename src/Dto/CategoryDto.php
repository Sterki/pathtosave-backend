<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $name;

    #[Assert\Choice(choices: ['expense', 'income'])]
    public string $type;

    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/')]
    public ?string $color = null;

    #[Assert\Length(max: 50)]
    public ?string $icon = null;
}
