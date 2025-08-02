<?php

namespace App\Models\Enum;

enum CustomerStatus: string
{
    use BaseEnum;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BLOCKED = 'blocked';
}
