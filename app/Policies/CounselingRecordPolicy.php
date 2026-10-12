<?php

namespace App\Policies;

use App\Models\CounselingRecord;
use App\Models\User;

class CounselingRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('BK') || $user->hasRole('Principal');
    }

    public function view(User $user, CounselingRecord $counselingRecord): bool
    {
        if ($user->hasRole('BK')) {
            // BK can only view their own assigned cases, regardless of confidentiality
            return $user->id === $counselingRecord->counselor_id;
        }

        if ($user->hasRole('Super Admin') || $user->hasRole('Principal')) {
            return $counselingRecord->confidentiality_level !== 'strict';
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('BK');
    }

    public function update(User $user, CounselingRecord $counselingRecord): bool
    {
        return $user->hasRole('BK') && $user->id === $counselingRecord->counselor_id;
    }

    public function delete(User $user, CounselingRecord $counselingRecord): bool
    {
        return $user->hasRole('BK') && $user->id === $counselingRecord->counselor_id 
            && in_array($counselingRecord->status, ['scheduled', 'cancelled']);
    }

    public function restore(User $user, CounselingRecord $counselingRecord): bool
    {
        return false;
    }

    public function forceDelete(User $user, CounselingRecord $counselingRecord): bool
    {
        return false;
    }
}
