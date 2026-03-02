<?php

declare(strict_types=1);

namespace App\Enums;

enum PostStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case PRIVATE = 'private';
    case REVIEWED = 'reviewed';
    case EDITED = 'edited';
    case APPROVED = 'approved';
    case ARCHIVED = 'archived';
    case EDIT_STATUS = 'edit_status';
    

}
