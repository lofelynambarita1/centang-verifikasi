<?php

namespace App\Exceptions;

use RuntimeException;

class AuditReportTemplateNotFound extends RuntimeException
{
    public function __construct(public readonly string $templatePath)
    {
        parent::__construct("Audit report Word template not found: {$templatePath}");
    }
}
