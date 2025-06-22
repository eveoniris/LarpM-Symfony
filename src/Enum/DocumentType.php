<?php

namespace App\Enum;

enum DocumentType: string
{
    case Objet = 'objets';
    case Blason = 'blasons';
    case Photos = 'photos';
    case Stock = 'stocks';
    case Doc = 'doc';
    case Document = 'documents';
    case Religion3D = 'religions/stl';
    case Rule = 'rules';
    case Langue = 'langue';
    case None = '';
    case Image = 'img';
}
