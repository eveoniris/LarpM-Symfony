<?php

namespace App\Enum;

enum FolderType: string
{
    case Private = 'private/';
    case Asset = 'assets/';
    case Photos = 'assets/img/';
    case Trombine = 'private/img/';
    case Stock = 'private/stock/';
    case Rule = 'private/rule/';
}
