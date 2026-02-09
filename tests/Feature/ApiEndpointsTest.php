<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * API Endpoints Integration Tests
 *
 * These tests verify that API endpoints are accessible and return expected responses.
 * They use the existing database data without creating new records.
 */
class ApiEndpointsTest extends TestCase
{
    // ==================== PUBLIC ENDPOINTS ====================

    public function test_doctors_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/doctors');
        $response->assertStatus(200);
    }

    public function test_clinics_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/clinics');
        $response->assertStatus(200);
    }

    public function test_banje_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/banje');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ]);
    }

    public function test_banje_filter_options_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/banje/filter-options');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'gradovi',
                    'regije',
                    'vrste',
                    'indikacije',
                    'terapije',
                ],
            ]);
    }

    public function test_domovi_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/domovi-njega');
        $response->assertStatus(200);
    }

    public function test_laboratorije_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/laboratorije');
        $response->assertStatus(200);
    }

    public function test_pitanja_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/pitanja');
        $response->assertStatus(200);
    }

    public function test_blog_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/blog');
        $response->assertStatus(200);
    }

    public function test_specijalnosti_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/specialties');
        $response->assertStatus(200);
    }

    public function test_gradovi_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/cities');
        $response->assertStatus(200);
    }

    public function test_sitemap_endpoint_returns_xml(): void
    {
        $response = $this->get('/api/sitemap.xml');
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_health_check_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    // ==================== SEARCH FUNCTIONALITY ====================

    public function test_doctors_search_works(): void
    {
        $response = $this->getJson('/api/doctors?search=test');
        $response->assertStatus(200);
    }

    public function test_clinics_search_works(): void
    {
        $response = $this->getJson('/api/clinics?search=test');
        $response->assertStatus(200);
    }

    public function test_banje_search_works(): void
    {
        $response = $this->getJson('/api/banje?search=test');
        $response->assertStatus(200);
    }

    // ==================== FILTERING ====================

    public function test_doctors_filter_by_city_works(): void
    {
        $response = $this->getJson('/api/doctors?grad=Sarajevo');
        $response->assertStatus(200);
    }

    public function test_clinics_filter_by_city_works(): void
    {
        $response = $this->getJson('/api/clinics?grad=Sarajevo');
        $response->assertStatus(200);
    }

    public function test_banje_filter_by_city_works(): void
    {
        $response = $this->getJson('/api/banje?grad=Sarajevo');
        $response->assertStatus(200);
    }

    // ==================== PAGINATION ====================

    public function test_doctors_pagination_works(): void
    {
        $response = $this->getJson('/api/doctors?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
            ]);
    }

    public function test_clinics_pagination_works(): void
    {
        $response = $this->getJson('/api/clinics?page=1&per_page=10');
        $response->assertStatus(200);
    }

    public function test_banje_pagination_works(): void
    {
        $response = $this->getJson('/api/banje?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    // ==================== SORTING ====================

    public function test_doctors_sorting_works(): void
    {
        $response = $this->getJson('/api/doctors?sort_by=ocjena&sort_order=desc');
        $response->assertStatus(200);
    }

    public function test_banje_sorting_works(): void
    {
        $response = $this->getJson('/api/banje?sort_by=ocjena&sort_order=desc');
        $response->assertStatus(200);
    }

    // ==================== AUTHENTICATION REQUIRED ENDPOINTS ====================

    public function test_protected_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_spa_dashboard_requires_auth(): void
    {
        $response = $this->getJson('/api/spa/profile');
        $response->assertStatus(401);
    }

    public function test_doctor_dashboard_requires_auth(): void
    {
        $response = $this->getJson('/api/doctors/me/profile');
        $response->assertStatus(401);
    }

    public function test_admin_endpoints_require_auth(): void
    {
        $response = $this->getJson('/api/admin/registration-requests');
        $response->assertStatus(401);
    }

    // ==================== RATE LIMITING ====================

    public function test_api_has_rate_limiting(): void
    {
        $response = $this->getJson('/api/doctors');
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    // ==================== CORS HEADERS ====================

    public function test_api_has_cors_headers(): void
    {
        $response = $this->withHeaders(['Origin' => 'http://localhost:5173'])
                         ->getJson('/api/doctors');

        // CORS headers should be present
        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin');

        // Verify the origin is allowed
        $allowedOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $this->assertTrue(
            $allowedOrigin === 'http://localhost:5173' || $allowedOrigin === '*',
            'localhost:5173 should be allowed as origin'
        );
    }

    // ==================== VALIDATION ====================

    public function test_registration_validates_input(): void
    {
        $response = $this->postJson('/api/register/doctor', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ime', 'prezime', 'email']);
    }

    public function test_login_validates_input(): void
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ==================== ERROR HANDLING ====================

    public function test_404_for_nonexistent_doctor(): void
    {
        $response = $this->getJson('/api/doctors/nonexistent-slug-12345');
        $response->assertStatus(404);
    }

    public function test_404_for_nonexistent_clinic(): void
    {
        $response = $this->getJson('/api/clinics/nonexistent-slug-12345');
        $response->assertStatus(404);
    }

    public function test_404_for_nonexistent_banja(): void
    {
        $response = $this->getJson('/api/banje/nonexistent-slug-12345');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Banja nije pronađena',
            ]);
    }
}
