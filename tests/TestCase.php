<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a test user with proper fields for the database schema.
     *
     * @param array $attributes Override default attributes
     * @return User
     */
    protected function createTestUser(array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User',
            'ime' => 'Test',
            'prezime' => 'User',
            'email' => 'test' . uniqid() . '@test.com',
            'password' => Hash::make('TestPassword123!'),
            'role' => 'patient',
        ];

        $user = User::create(array_merge($defaults, $attributes));

        // Assign role if specified
        $role = $attributes['role'] ?? 'patient';
        if ($user->roles()->count() === 0) {
            $user->assignRole($role);
        }

        return $user;
    }
}
