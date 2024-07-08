<?php

namespace App\Enums;

enum FileTypeEnum: string
{
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case IMAGE = 'image';
    case TEXT = 'text';
}
