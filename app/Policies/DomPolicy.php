<?php

namespace App\Policies;

use App\Models\Dom;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DomPolicy
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
    public function view(?User $user, Dom $dom): bool
    {
        // Public can view active domovi
        if ($dom->aktivan) {
            return true;
        }

        // Owner and admin can view inactive domovi
        if ($user) {
            return $user->hasRole('admin') || $user->id === $dom->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'dom_manager', 'care_home_manager', 'care_home']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin') ||
               (($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home')) && $user->id === $dom->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin') ||
               (($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home')) && $user->id === $dom->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can verify the model.
     */
    public function verify(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can activate/deactivate the model.
     */
    public function toggleStatus(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage inquiries.
     */
    public function manageInquiries(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin') ||
               (($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home')) && $user->id === $dom->user_id);
    }

    /**
     * Determine whether the user can manage reviews.
     */
    public function manageReviews(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin') ||
               (($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home')) && $user->id === $dom->user_id);
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user, Dom $dom): bool
    {
        return $user->hasRole('admin') ||
               (($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home')) && $user->id === $dom->user_id);
    }
}
