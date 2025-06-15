<?php

namespace App\Enum;

enum VisibilityType: string
{
    use EnumTraits;

    case GROUPE_MEMBER = 'GROUPE_MEMBER';
    case GROUPE_OWNER = 'GROUPE_OWNER';
    case PRIVATE = 'PRIVATE';
    case AUTHOR = 'AUTHOR';
    case PUBLIC = 'PUBLIC';

    public function isAuthor(): bool
    {
        return self::AUTHOR->value === $this->value;
    }

    public function isGroupeMember(): bool
    {
        return self::GROUPE_MEMBER->value === $this->value;
    }

    public function isGroupeOwner(): bool
    {
        return self::GROUPE_OWNER->value === $this->value;
    }

    public function isPrivate(): bool
    {
        return self::PRIVATE->value === $this->value;
    }

    public function isPublic(): bool
    {
        return self::PUBLIC->value === $this->value;
    }
}
