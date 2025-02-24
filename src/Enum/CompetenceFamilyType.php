<?php

namespace App\Enum;

enum CompetenceFamilyType: string
{
    use EnumTraits;

    case AGILITY = 'Agility'; // Agilité
    case ALCHEMY = 'Alchemy'; // Alchimie
    case ARMOR = 'Armor'; // Armure
    case ARTS = 'Arts'; // Arts
    case BUSINESS = 'Business'; // Commerce
    case CHARISMA = 'Charisma'; // Charisme
    case CRAFTSMANSHIP = 'Craftsmanship'; // Artisanat
    case DISCRETION = 'Discretion'; // Discrétion
    case FORGE = 'Forge'; // Forge
    case HEALING = 'Healing'; // Guérison
    case HERBALISM = 'Herbalism'; // Herboristerie
    case LITERATURE = 'Literature'; // Littérature
    case MAGIC = 'Magic'; // Magie
    case MAPPING = 'Mapping'; // Cartographie
    case NOBILITY = 'Nobility'; // Noblesse
    case OBSERVATION = 'Observation'; // Observation
    case ONE_HANDED_WEAPON = '1-handed weapons'; // Armes à 1 main
    case POLEARMS = 'Polearms'; // Armes d'hast
    case POLITICAL = 'Political'; // Politique
    case PRIESTHOOD = 'Priesthood'; // Prêtrise
    case PROTECTION = 'Protection'; // Protection
    case RANGED_WEAPONS = 'Ranged Weapons'; // Armes à distance
    case RESISTANCE = 'Resistance'; // Résistance
    case RITUALISM = 'Ritualism'; // Ritualisme
    case SAVAGERY = 'Savagery'; // Sauvagerie
    case SNEAK_ATTACK = 'Sneak attack'; // Attaque sournoise
    case SPYING = 'Spying'; // Espionnage
    case STRATEGY = 'Strategy'; // Stratégie
    case SURVIVAL = 'Survival'; // Survie
    case THEFT = 'Theft'; // Vol
    case TORTURE = 'Torture'; // Torture
    case TWO_HANDED_WEAPON = '2-handed weapons'; // Armes à 2 mains
    case WEALTH = 'Wealth'; // Richesse
    case SECRET = 'SECRET';

    public function getId(): int
    {
        return self::getTypeId($this);
    }

    public static function getTypeId(self $type): int
    {
        return match ($type->value) {
            self::AGILITY->value => 1,
            self::ALCHEMY->value => 4,
            self::ARMOR->value => 19,
            self::ARTS->value => 28,
            self::BUSINESS->value => 8,
            self::CHARISMA->value => 21,
            self::CRAFTSMANSHIP->value => 25,
            self::DISCRETION->value => 11,
            self::FORGE->value => 22,
            self::HEALING->value => 17,
            self::HERBALISM->value => 2,
            self::LITERATURE->value => 20,
            self::MAGIC->value => 23,
            self::MAPPING->value => 5,
            self::NOBILITY->value => 26,
            self::OBSERVATION->value => 29,
            self::ONE_HANDED_WEAPON->value => 7,
            self::POLEARMS->value => 16,
            self::POLITICAL->value => 32,
            self::PRIESTHOOD->value => 3,
            self::PROTECTION->value => 6,
            self::RANGED_WEAPONS->value => 13,
            self::RESISTANCE->value => 9,
            self::RITUALISM->value => 15,
            self::SAVAGERY->value => 18,
            self::SNEAK_ATTACK->value => 31,
            self::SPYING->value => 14,
            self::STRATEGY->value => 24,
            self::SURVIVAL->value => 27,
            self::THEFT->value => 33,
            self::TORTURE->value => 30,
            self::TWO_HANDED_WEAPON->value => 10,
            self::WEALTH->value => 12,
            default => -1,
        };
    }

    public static function getFromLabel(string $label): ?CompetenceFamilyType
    {
        if ($type = self::tryFrom($label)) {
            return $type;
        }

        return self::tryFromOlder($label);
    }

    public static function tryFromOlder(string $value): ?CompetenceFamilyType
    {
        return match ($value) {
            'AgilitÃ©' => self::AGILITY,
            'Armes d\'hast' => self::POLEARMS,
            'Armes Ã  1 main', 'Armes à 1 main' => self::ONE_HANDED_WEAPON,
            'Armes Ã  2 mains', 'Armes à 2 mains' => self::TWO_HANDED_WEAPON,
            'Armes Ã  distance', 'Amres à distance' => self::RANGED_WEAPONS,
            'Armure' => self::ARMOR,
            'Artisanat' => self::CRAFTSMANSHIP,
            'Arts' => self::ARTS,
            'Attaque sournoise' => self::SNEAK_ATTACK,
            'Cartographie' => self::MAPPING,
            'Charisme' => self::CHARISMA,
            'Commerce' => self::BUSINESS,
            'DiscrÃ©tion', 'Discrétion' => self::DISCRETION,
            'Espionnage' => self::SPYING,
            'Forge' => self::FORGE,
            'GuÃ©rison', 'Guérison' => self::HEALING,
            'LittÃ©rature', 'Littérature' => self::LITERATURE,
            'Magie' => self::MAGIC,
            'Noblesse' => self::NOBILITY,
            'Observation' => self::OBSERVATION,
            'Politique' => self::POLITICAL,
            'Protection' => self::PROTECTION,
            'Prêtrise', 'PrÃªtrise' => self::PRIESTHOOD,
            'Richesse' => self::WEALTH,
            'Ritualisme' => self::RITUALISM,
            'RÃ©sistance', 'Résistance' => self::RESISTANCE,
            'Sauvagerie' => self::SAVAGERY,
            'StratÃ©gie', 'Stratégie' => self::STRATEGY,
            'Survie' => self::SURVIVAL,
            'Torture' => self::TORTURE,
            'Vol' => self::THEFT,
            default => null,
        };
    }
}
