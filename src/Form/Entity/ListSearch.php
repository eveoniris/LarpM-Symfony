<?php

namespace App\Form\Entity;

class ListSearch
{
    protected ?string $type = '';
    protected ?string $value = '';

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /*
     * If class is extended it's can be used to define type_choices
     */
    public function getTypeChoices(): array
    {
        return [];
    }
}
