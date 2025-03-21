<?php

namespace App\Enum;

enum DocumentType: string
{
    case Objet = 'objets';
    case Blason = 'blasons';
    case Photos = 'photos';
    case Stock = 'stocks';
    case Documents = 'doc';
    case Rule = 'rules';
    case Langue = 'langue';
    case None = '';
    case Image = 'img';
}
