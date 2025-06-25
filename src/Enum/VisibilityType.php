<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

enum VisibilityType: string
{
    use EnumTraits;

    case GROUPE_MEMBER = 'GROUPE_MEMBER';
    case GROUPE_OWNER = 'GROUPE_OWNER';
    case PRIVATE = 'PRIVATE';
    case AUTHOR = 'AUTHOR';
    case PUBLIC = 'PUBLIC';

    public function getLabel(): string
    {
        return $this->getLabels()[$this->value] ?? '';
    }

    public function getLabels(): array
    {
        return [
            self::PRIVATE->value => 'Seuls les scénaristes peuvent voir ceci',
            self::PUBLIC->value => 'Tous les joueurs peuvent voir ceci',
            self::GROUPE_MEMBER->value => 'Seuls les membres du groupe peuvent voir ceci',
            self::GROUPE_OWNER->value => 'Seul le chef de groupe peut voir ceci',
            self::AUTHOR->value => 'Seul l\'auteur peut voir ceci',
        ];

    }

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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::PRIVATE => $translator->trans('PRIVATE', domain: 'enum', locale: $locale),
            self::PUBLIC => $translator->trans('PUBLIC', domain: 'enum', locale: $locale),
            self::GROUPE_MEMBER => $translator->trans('GROUPE_MEMBER', domain: 'enum', locale: $locale),
            self::GROUPE_OWNER => $translator->trans('GROUPE_OWNER', domain: 'enum', locale: $locale),
            self::AUTHOR => $translator->trans('AUTHOR', domain: 'enum', locale: $locale),
        };
    }
}
