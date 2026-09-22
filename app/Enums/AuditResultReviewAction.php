<?php

namespace App\Enums;

enum AuditResultReviewAction: string
{
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
}
