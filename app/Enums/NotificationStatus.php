<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationStatus: string
{
    case READ = 'read';
    case UNREAD = 'unread';
}