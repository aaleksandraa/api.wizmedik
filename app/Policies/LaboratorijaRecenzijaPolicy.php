<?php

namespace App\Policies;

use App\Models\LaboratorijaRecenzija;
use App\Models\User;

class LaboratorijaRecenzijaPolicy
{
    /**
     * Admin moderation: list all reviews (including pending) for a laboratory.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admin moderation: approve / reject / bulk-approve reviews.
     */
    public function update(User $user, ?LaboratorijaRecenzija $recenzija = null): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Admin moderation: delete a review.
     */
    public function delete(User $user, ?LaboratorijaRecenzija $recenzija = null): bool
    {
        return $user->hasRole('admin');
    }
}
