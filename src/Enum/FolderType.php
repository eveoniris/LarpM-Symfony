<?php

namespace App\Enum;

enum FolderType: string
{
    case Private = 'private/';
    case Asset = 'assets/';
    case Photos = 'assets/img/';
    case Stock = 'private/stock/';
}
