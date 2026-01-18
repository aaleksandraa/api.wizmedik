<?php

namespace App\Policies;

use App\Models\Banja;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BanjaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Public access
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Banja $banja): bool
    {
        // Public can view active banje
        if ($banja->aktivan) {
            return true;
        }

        // Owner and admin can view inactive banje
        if ($user) {
            return $user->hasRole('admin') || $user->id === $banja->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'spa_manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin') ||
               ($user->hasRole('spa_manager') && $user->id === $banja->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin') ||
               ($user->hasRole('spa_manager') && $user->id === $banja->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can verify the model.
     */
    public function verify(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can activate/deactivate the model.
     */
    public function toggleStatus(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage inquiries.
     */
    public function manageInquiries(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin') ||
               ($user->hasRole('spa_manager') && $user->id === $banja->user_id);
    }

    /**
     * Determine whether the user can manage reviews.
     */
    public function manageReviews(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin') ||
               ($user->hasRole('spa_manager') && $user->id === $banja->user_id);
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user, Banja $banja): bool
    {
        return $user->hasRole('admin') ||
               ($user->hasRole('spa_manager') && $user->id === $banja->user_id);
    }
}
