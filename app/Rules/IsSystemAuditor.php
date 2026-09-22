<?php

namespace App\Rules;

use App\Enums\SystemRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class IsSystemAuditor implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = DB::table('users')->where('id', $value)->value('system_role');

        if ($role !== SystemRole::Auditor->value) {
            $fail('User terpilih bukan berperan sebagai auditor.');
        }
    }
}
