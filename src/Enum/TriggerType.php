<?php

namespace App\Enum;

enum TriggerType: string
{
    use EnumTraits;

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
    case LITTERATURE_INITIE = 'LITTERATURE INITIE';
    case LITTERATURE_EXPERT = 'LITTERATURE EXPERT';
    case LITTERATURE_COURANTE = 'LITTERATURE COURANTE';
    case LITTERATURE_COURANT = 'LITTERATURE COURANT';
    case LITTERATURE_COMMUNE = 'LITTERATURE COMMUNE';
    case PRETRISE_INITIE = 'PRETRISE INITIE';
    case TECHNOLOGIE = 'TECHNOLOGIE';
    case DOMAINE_MAGIE = 'DOMAINE MAGIE';
    case LANGUE_COURANTE = 'LANGUE COURANTE';
    case LANGUE_ANCIENNE = 'LANGUE ANCIENNE';

    public function getDescription(): string
    {
        match ($this->value) {
            self::ALCHIMIE_APPRENTI => 'permet de choisir une nouvelle recette de potion de niveau Apprenti',
            self::ALCHIMIE_INITIE => 'permet de choisir une nouvelle recette de potion de niveau Initié',
            self::ALCHIMIE_EXPERT => 'permet de choisir une nouvelle recette de potion de niveau Expert',
            self::ALCHIMIE_MAITRE => 'permet de choisir une nouvelle recette de potion de niveau Maître',
            self::MAGIE_APPRENTI => 'permet de choisir un domaine de magie',
            self::MAGIE_EXPERT => 'permet de choisir un domaine de magie',
            self::SORT_APPRENTI => 'permet de choisir un sort de niveau Apprenti',
            self::SORT_INITIE => 'permet de choisir un sort de niveau Initié',
            self::SORT_EXPERT => 'permet de choisir un sort de niveau Expert',
            self::SORT_MAITRE => 'permet de choisir un sort de niveau Maître',
            self::LITTERATURE_COURANTE, self::LITTERATURE_COURANT, self::LITTERATURE_COMMUNE => 'permet de choisir une langue supplémentaire sauf parmis les anciennes',
            self::LITTERATURE_INITIE => 'permet de choisir trois langues supplémentaires sauf parmis les anciennes',
            self::LITTERATURE_EXPERT => 'permet de choisir trois langues supplémentaires dont 1 ancienne',
            self::PRETRISE_INITIE => 'permet de choisir trois descriptifs de religion',
            self::TECHNOLOGIE => 'permet de choisir une technologie',
            self::DOMAINE_MAGIE => 'permet de choisir un domaine de magie',
            self::LANGUE_COURANTE => 'permet de choisir une langue commune',
            self::LANGUE_ANCIENNE => 'permet de choisir une langue ancienne',
            default => throw new \Exception('Unexpected match value '.$this->value),
        };
    }

    public function isLangueAncienne(): bool
    {
        return $this->value === self::LANGUE_ANCIENNE->value;
    }

    public function isLitreratureCommune(): bool
    {
        return $this->value === self::LITTERATURE_COMMUNE->value;
    }

    public function isLitreratureCourante(): bool
    {
        return $this->value === self::LITTERATURE_COURANTE->value
            || $this->value === self::LITTERATURE_COURANT->value;
    }
}
