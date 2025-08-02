<?php

namespace App\Models\Enum;

enum Gender: string
{
    use BaseEnum;

    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
}
