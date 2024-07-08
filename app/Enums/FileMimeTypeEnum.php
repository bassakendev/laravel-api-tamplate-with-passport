<?php

namespace App\Enums;

enum FileMimeTypeEnum: string
{
    case AUDIO = 'wav,ogg,mp3';
    case VIDEO = 'mp4,ogg,webm';
    case IMAGE = 'jpeg,png,gif,jpg,webp';
    case TEXT = 'plain,docx,pdf,msword,txt';
}
