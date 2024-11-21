<?php

namespace App\Enum;

enum DocumentType: string
{
    case Objet = 'objets';
    case Blason = 'blasons';
    case Photos = 'photos';
    case Stock = 'stocks';
    case Documents = 'documents';
    case None = '';
    case Image = 'img';
}
