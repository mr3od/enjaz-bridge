<?php

namespace App\Enums;

enum EnjazStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
