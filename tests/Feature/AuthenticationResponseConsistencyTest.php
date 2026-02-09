<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Property-Based Test: Authentication Response Consistency
 *
 * Property 3: For any login attempt (valid or invalid), the authentication system
 * should return properly formatted responses with appropriate status codes and error
 * messages that don't expose sensitive information
 *
 * Validates: Requirements 2.1, 2.2, 2.4, 5.4
 * Feature: dev-environment-connectivity, Property 3: Authentication Response Consistency
 */
class AuthenticationResponseConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Property test: Valid credentials should return consistent success response format
     *
     * @test
     */
    public function test_valid_credentials_return_consistent_success_format(): void
    {
        // Generate test data for property-based testing
        $testCases = $this->generateValidCredentialTestCases(10);

        foreach ($testCases as $case) {
            // Arrange: Create user with generated credentials
            $user = User::factory()->create([
                'email' => $case['email'],
                'password' => Hash::make($case['password']),
                'ime' => $case['ime'],
                'prezime' => $case['prezime'],
            ]);
            $user->assignRole('patient');

            // Act: Attempt login
            $response = $this->postJson('/api/login', [
                'email' => $case['email'],
                'password' => $case['password'],
            ]);

            // Assert: Check response structure and format
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'ime',
                        'prezime',
                        'email',
                        'telefon',
                        'grad',
                        'role',
                    ],
                    'token'
                ]);

            // Assert: Check response content consistency
            $responseData = $response->json();
            $this->assertEquals('Prijava uspješna', $responseData['message']);
            $this->assertEquals($case['email'], $responseData['user']['email']);
            $this->assertEquals($case['ime'], $responseData['user']['ime']);
            $this->assertEquals($case['prezime'], $responseData['user']['prezime']);
            $this->assertEquals('patient', $responseData['user']['role']);
            $this->assertNotEmpty($responseData['token']);

            // Assert: Token should be a valid string
            $this->assertIsString($responseData['token']);
            $this->assertGreaterThan(10, strlen($responseData['token']));

            // Clean up for next iteration
            $user->delete();
        }
    }

    /**
     * Property test: Invalid credentials should return consistent error response format
     *
     * @test
     */
    public function test_invalid_credentials_return_consistent_error_format(): void
    {
        // Generate test data for property-based testing
        $testCases = $this->generateInvalidCredentialTestCases(10);

        foreach ($testCases as $case) {
            // Arrange: Create user if needed for wrong password tests
            if ($case['type'] === 'wrong_password') {
                $user = User::factory()->create([
                    'email' => $case['email'],
                    'password' => Hash::make('correct_password'),
                ]);
                $user->assignRole('patient');
            }

            // Act: Attempt login with invalid credentials
            $response = $this->postJson('/api/login', [
                'email' => $case['email'],
                'password' => $case['password'],
            ]);

            // Assert: Check error response structure and format
            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);

            // Assert: Check error message consistency
            $responseData = $response->json();
            $this->assertEquals('Neispravni pristupni podaci.', $responseData['message']);
            $this->assertArrayHasKey('email', $responseData['errors']);
            $this->assertIsArray($responseData['errors']['email']);
            $this->assertEquals(['Neispravni pristupni podaci.'], $responseData['errors']['email']);

            // Assert: Response should not expose sensitive information
            $responseJson = $response->getContent();
            $this->assertStringNotContainsString('hash', strtolower($responseJson));
            $this->assertStringNotContainsString('database', strtolower($responseJson));
            $this->assertStringNotContainsString('sql', strtolower($responseJson));
            $this->assertStringNotContainsString('exception', strtolower($responseJson));
            $this->assertStringNotContainsString('stack', strtolower($responseJson));

            // Clean up for next iteration
            if (isset($user)) {
                $user->delete();
                unset($user);
            }
        }
    }

    /**
     * Property test: Malformed requests should return consistent validation error format
     *
     * @test
     */
    public function test_malformed_requests_return_consistent_validation_format(): void
    {
        // Generate test data for property-based testing
        $testCases = $this->generateMalformedRequestTestCases(8);

        foreach ($testCases as $case) {
            // Act: Send malformed request
            $response = $this->postJson('/api/login', $case['data']);

            // Assert: Check validation error response structure
            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

            // Assert: Check validation message consistency
            $responseData = $response->json();
            $this->assertIsString($responseData['message']);
            $this->assertNotEmpty($responseData['message']);
            $this->assertIsArray($responseData['errors']);

            // Assert: Expected validation errors should be present
            foreach ($case['expected_errors'] as $field) {
                $this->assertArrayHasKey($field, $responseData['errors']);
                $this->assertIsArray($responseData['errors'][$field]);
                $this->assertNotEmpty($responseData['errors'][$field]);
            }

            // Assert: Response should not expose sensitive information
            $responseJson = $response->getContent();
            $this->assertStringNotContainsString('hash', strtolower($responseJson));
            $this->assertStringNotContainsString('database', strtolower($responseJson));
            $this->assertStringNotContainsString('sql', strtolower($responseJson));
            $this->assertStringNotContainsString('exception', strtolower($responseJson));
            $this->assertStringNotContainsString('stack', strtolower($responseJson));
        }
    }

    /**
     * Generate valid credential test cases for property-based testing
     */
    private function generateValidCredentialTestCases(int $count): array
    {
        $cases = [];

        for ($i = 0; $i < $count; $i++) {
            $cases[] = [
                'email' => 'test' . $i . '@example.com',
                'password' => 'TestPassword' . $i . '!',
                'ime' => 'Test' . $i,
                'prezime' => 'User' . $i,
            ];
        }

        return $cases;
    }

    /**
     * Generate invalid credential test cases for property-based testing
     */
    private function generateInvalidCredentialTestCases(int $count): array
    {
        $cases = [];

        // Generate cases with non-existent emails
        for ($i = 0; $i < $count / 2; $i++) {
            $cases[] = [
                'type' => 'non_existent_email',
                'email' => 'nonexistent' . $i . '@example.com',
                'password' => 'SomePassword' . $i . '!',
            ];
        }

        // Generate cases with wrong passwords
        for ($i = 0; $i < $count / 2; $i++) {
            $cases[] = [
                'type' => 'wrong_password',
                'email' => 'wrongpass' . $i . '@example.com',
                'password' => 'wrong_password' . $i,
            ];
        }

        return $cases;
    }

    /**
     * Generate malformed request test cases for property-based testing
     */
    private function generateMalformedRequestTestCases(int $count): array
    {
        return [
            [
                'data' => [],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => ''],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['password' => ''],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => '', 'password' => ''],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => 'invalid-email'],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => 'invalid-email', 'password' => 'pass'],
                'expected_errors' => ['email']
            ],
            [
                'data' => ['email' => null, 'password' => null],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => 123, 'password' => 456],
                'expected_errors' => ['email']
            ],
        ];
    }
}
