<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Letter $letter): bool
    {
        return $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'BK', 'Tata Tertib', 'Principal']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Letter $letter): bool
    {
        return $user->hasAnyRole(['Super Admin', 'BK', 'Tata Tertib', 'Principal']);
    }

    /**
     * Determine whether the user can update letter templates.
     */
    public function updateTemplate(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'BK', 'Principal', 'Tata Tertib']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Letter $letter): bool
    {
        return $user->hasRole('Super Admin');
    }
}
