<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageTrigger extends BasePersonnageTrigger
{
    public const TAG_TECHNOLOGIE = 'TECHNOLOGIE';
    public const TAG_DOMAINE_MAGIE = 'DOMAINE MAGIE';
    public const TAG_SORT_APPRENTI = 'SORT APPRENTI';
    public const TAG_SORT_INITIE = 'SORT INITIE';
    public const TAG_SORT_EXPERT = 'SORT EXPERT';
    public const TAG_SORT_MAITRE = 'SORT MAITRE';
    public const TAG_ALCHIMIE_APPRENTI = 'ALCHIMIE APPRENTI';
    public const TAG_ALCHIMIE_INITIE = 'ALCHIMIE INITIE';
    public const TAG_ALCHIMIE_EXPERT = 'ALCHIMIE EXPERT';
    public const TAG_ALCHIMIE_MAITRE = 'ALCHIMIE MAITRE';

    public const TAG_NOBLESSE_APPRENTI = 'NOBLESSE APPRENTI';
    public const TAG_NOBLESSE_INITIE = 'NOBLESSE INITIE';
    public const TAG_NOBLESSE_EXPERT = 'NOBLESSE EXPERT';
    public const TAG_NOBLESSE_MAITRE = 'NOBLESSE MAITRE';

    public const TAG_PRETRISE_APPRENTI = 'PRETRISE APPRENTI';
    public const TAG_PRETRISE_INITIE = 'PRETRISE INITIE';
    public const TAG_PRETRISE_EXPERT = 'PRETRISE EXPERT';
    public const TAG_PRETRISE_MAITRE = 'PRETRISE MAITRE';
    public const TAG_LANGUE_COURANTE = 'LANGUE COURANTE';
    public const TAG_LANGUE_ANCIENNE = 'LANGUE ANCIENNE';
}
