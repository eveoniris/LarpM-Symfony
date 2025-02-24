<?php

namespace App\Entity;

use App\Enum\BonusType;
use App\Enum\Status;

trait BonusTrait
{
    public function getStatus(): Status
    {
        return Status::tryFrom($this->status) ?? Status::ERROR;
    }

    public function setStatus(string|Status|null $status): static
    {
        if ($status instanceof Status) {
            $this->status = $status->value;

            return $this;
        }

        $this->status = $status;

        return $this;
    }

    public function isValid(): bool
    {
        return Status::ACTIVE === $this->getStatus() && $this->bonus?->isValid();
    }
}
