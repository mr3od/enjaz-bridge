<?php

namespace App\Enums;

enum ApplicantStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Processing = 'processing';
    case Extracted = 'extracted';
    case Failed = 'failed';
}
