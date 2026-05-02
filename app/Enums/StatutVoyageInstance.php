<?php

namespace App\Enums;

enum StatutVoyageInstance: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case INACTIF = 'INACTIF';
    case RETARDE = 'RETARDE';
    case ANNULE = 'ANNULE';


    public static function values():array
    {
        return array_column(self::cases(), 'value');
    }
}
