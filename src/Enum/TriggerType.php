<?php

namespace App\Enum;

enum TriggerType: string
{
    use EnumTraits;

    case FRUITS_ET_LEGUMES = '5 Fruits et Légumes x 1';
    case ALCHIMIE_APPRENTI = 'ALCHIMIE APPRENTI';
    case ALCHIMIE_INITIE = 'ALCHIMIE INITIE';
    case ALCHIMIE_EXPERT = 'ALCHIMIE EXPERT';
    case ALCHIMIE_MAITRE = 'ALCHIMIE MAITRE';
    case MAGIE_APPRENTI = 'MAGIE APPRENTI';
    case MAGIE_EXPERT = 'MAGIE EXPERT';
    case SORT_APPRENTI = 'SORT APPRENTI';
    case SORT_INITIE = 'SORT INITIE';
    case SORT_EXPERT = 'SORT EXPERT';
    case SORT_MAITRE = 'SORT MAITRE';
    case PRETRISE_INITIE = 'PRETRISE INITIE';
    case TECHNOLOGIE = 'TECHNOLOGIE';
    case DOMAINE_MAGIE = 'DOMAINE MAGIE';
    case LANGUE_COURANTE = 'LANGUE COURANTE';
    case LANGUE_ANCIENNE = 'LANGUE ANCIENNE';

    public function getDescription(): string
    {
        return match ($this->value) {
            self::ALCHIMIE_APPRENTI->value => 'permet de choisir une nouvelle recette de potion de niveau Apprenti',
            self::ALCHIMIE_INITIE->value => 'permet de choisir une nouvelle recette de potion de niveau Initié',
            self::ALCHIMIE_EXPERT->value => 'permet de choisir une nouvelle recette de potion de niveau Expert',
            self::ALCHIMIE_MAITRE->value => 'permet de choisir une nouvelle recette de potion de niveau Maître',
            //self::MAGIE_APPRENTI->value => 'permet de choisir un domaine de magie',
            self::SORT_APPRENTI->value => 'permet de choisir un sort de niveau Apprenti',
            self::SORT_INITIE->value => 'permet de choisir un sort de niveau Initié',
            self::SORT_EXPERT->value => 'permet de choisir un sort de niveau Expert',
            self::SORT_MAITRE->value => 'permet de choisir un sort de niveau Maître',
            self::PRETRISE_INITIE->value => 'permet de choisir trois descriptifs de religion',
            self::TECHNOLOGIE->value => 'permet de choisir une technologie',
            self::DOMAINE_MAGIE->value => 'permet de choisir un domaine de magie',
            self::LANGUE_COURANTE->value => 'permet de choisir une langue commune',
            self::LANGUE_ANCIENNE->value => 'permet de choisir une langue ancienne',
            default => throw new \Exception('Unexpected match value '.$this->value),
        };
    }

    public function isAlchimieApprenti(): bool
    {
        return $this->value === self::ALCHIMIE_APPRENTI->value;
    }

    public function isAlchimieExpert(): bool
    {
        return $this->value === self::ALCHIMIE_EXPERT->value;
    }

    public function isAlchimieInitie(): bool
    {
        return $this->value === self::ALCHIMIE_APPRENTI->value;
    }

    public function isAlchimieMaitre(): bool
    {
        return $this->value === self::ALCHIMIE_MAITRE->value;
    }

    public function isDomaineMagie(): bool
    {
        return $this->value === self::DOMAINE_MAGIE->value;
    }

    public function isLangueAncienne(): bool
    {
        return $this->value === self::LANGUE_ANCIENNE->value;
    }

    public function isLangueCourante(): bool
    {
        return $this->value === self::LANGUE_COURANTE->value;
    }

    public function isPretriseInitie(): bool
    {
        return $this->value === self::PRETRISE_INITIE->value;
    }

    public function isSortApprenti(): bool
    {
        return $this->value === self::SORT_APPRENTI->value;
    }

    public function isSortExpert(): bool
    {
        return $this->value === self::SORT_EXPERT->value;
    }

    public function isSortInitie(): bool
    {
        return $this->value === self::SORT_INITIE->value;
    }

    public function isSortMaitre(): bool
    {
        return $this->value === self::SORT_MAITRE->value;
    }

    public function isTechnologie(): bool
    {
        return $this->value === self::TECHNOLOGIE->value;
    }
}
