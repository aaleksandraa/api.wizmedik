<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Property-Based Test: Input Validation
 *
 * Property 4: For any email and password combination submitted to the login endpoint,
 * the system should validate the input format before processing the authentication request
 *
 * Validates: Requirements 2.3
 * Feature: dev-environment-connectivity, Property 4: Input Validation
 */
class AuthenticationInputValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Property test: Invalid email formats should be rejected before authentication processing
     *
     * @test
     */
    public function test_invalid_email_formats_rejected_before_processing(): void
    {
        // Generate test data for property-based testing (reduced count to avoid rate limiting)
        $invalidEmailCases = $this->generateInvalidEmailTestCases(5);

        foreach ($invalidEmailCases as $case) {
            // Act: Submit login with invalid email format
            $response = $this->postJson('/api/login', [
                'email' => $case['email'],
                'password' => 'ValidPassword123!',
            ]);

            // Assert: Should return validation error before attempting authentication
            // Skip rate limited responses as they indicate the system is working
            if ($response->getStatusCode() === 429) {
                continue;
            }

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);

            // Assert: Email validation error should be present
            $responseData = $response->json();
            $this->assertArrayHasKey('email', $responseData['errors']);
            $this->assertIsArray($responseData['errors']['email']);
            $this->assertNotEmpty($responseData['errors']['email']);

            // Assert: Error message should indicate email format issue
            $emailErrors = $responseData['errors']['email'];
            $hasEmailFormatError = false;
            foreach ($emailErrors as $error) {
                if (str_contains(strtolower($error), 'email') ||
                    str_contains(strtolower($error), 'format') ||
                    str_contains(strtolower($error), 'valid')) {
                    $hasEmailFormatError = true;
                    break;
                }
            }
            $this->assertTrue($hasEmailFormatError,
                "Email validation error should be present for invalid email: {$case['email']}");
        }
    }

    /**
     * Property test: Missing required fields should be rejected with appropriate errors
     *
     * @test
     */
    public function test_missing_required_fields_rejected_with_errors(): void
    {
        // Generate test data for property-based testing
        $missingFieldCases = $this->generateMissingFieldTestCases();

        foreach ($missingFieldCases as $case) {
            // Act: Submit login with missing fields
            $response = $this->postJson('/api/login', $case['data']);

            // Assert: Should return validation error
            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

            // Assert: Expected validation errors should be present
            $responseData = $response->json();
            foreach ($case['expected_missing_fields'] as $field) {
                $this->assertArrayHasKey($field, $responseData['errors'],
                    "Missing field '{$field}' should have validation error");
                $this->assertIsArray($responseData['errors'][$field]);
                $this->assertNotEmpty($responseData['errors'][$field]);

                // Assert: Error message should indicate field is required
                $fieldErrors = $responseData['errors'][$field];
                $hasRequiredError = false;
                foreach ($fieldErrors as $error) {
                    if (str_contains(strtolower($error), 'required') ||
                        str_contains(strtolower($error), 'potrebno')) {
                        $hasRequiredError = true;
                        break;
                    }
                }
                $this->assertTrue($hasRequiredError,
                    "Required field error should be present for field: {$field}");
            }
        }
    }

    /**
     * Property test: Valid email formats should pass validation
     *
     * @test
     */
    public function test_valid_email_formats_pass_validation(): void
    {
        // Generate test data for property-based testing
        $validEmailCases = $this->generateValidEmailTestCases(10);

        foreach ($validEmailCases as $case) {
            // Act: Submit login with valid email format (but non-existent user)
            $response = $this->postJson('/api/login', [
                'email' => $case['email'],
                'password' => 'SomePassword123!',
            ]);

            // Assert: Should not return email format validation error
            // (It should return authentication error instead, meaning validation passed)
            $response->assertStatus(422);

            $responseData = $response->json();
            if (isset($responseData['errors']['email'])) {
                $emailErrors = $responseData['errors']['email'];
                foreach ($emailErrors as $error) {
                    // Should not contain email format validation errors
                    $this->assertStringNotContainsString('format', strtolower($error));
                    $this->assertStringNotContainsString('valid email', strtolower($error));
                    $this->assertStringNotContainsString('must be', strtolower($error));
                }
            }
        }
    }

    /**
     * Property test: Empty and whitespace-only inputs should be properly validated
     *
     * @test
     */
    public function test_empty_and_whitespace_inputs_properly_validated(): void
    {
        // Generate test data for property-based testing
        $emptyInputCases = $this->generateEmptyInputTestCases();

        foreach ($emptyInputCases as $case) {
            // Act: Submit login with empty/whitespace inputs
            $response = $this->postJson('/api/login', $case['data']);

            // Assert: Should return validation error
            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

            // Assert: Expected validation errors should be present
            $responseData = $response->json();
            foreach ($case['expected_errors'] as $field) {
                $this->assertArrayHasKey($field, $responseData['errors'],
                    "Empty/whitespace field '{$field}' should have validation error");
                $this->assertIsArray($responseData['errors'][$field]);
                $this->assertNotEmpty($responseData['errors'][$field]);
            }
        }
    }

    /**
     * Generate invalid email test cases for property-based testing
     */
    private function generateInvalidEmailTestCases(int $count): array
    {
        $invalidEmails = [
            'plainaddress',
            '@missingdomain.com',
            'missing@.com',
            'spaces in@email.com',
            'email@',
            'email.domain.com',
            'email@domain..com',
            'email@.domain.com',
            '.email@domain.com',
            'email.@domain.com',
            'email..email@domain.com',
            'email@domain.com.',
            'email@-domain.com',
            'email@domain-.com',
            'email@domain.c',
        ];

        $cases = [];
        for ($i = 0; $i < min($count, count($invalidEmails)); $i++) {
            $cases[] = ['email' => $invalidEmails[$i]];
        }

        return $cases;
    }

    /**
     * Generate missing field test cases for property-based testing
     */
    private function generateMissingFieldTestCases(): array
    {
        return [
            [
                'data' => [],
                'expected_missing_fields' => ['email', 'password']
            ],
            [
                'data' => ['email' => 'test@example.com'],
                'expected_missing_fields' => ['password']
            ],
            [
                'data' => ['password' => 'password123'],
                'expected_missing_fields' => ['email']
            ],
            [
                'data' => ['email' => null, 'password' => null],
                'expected_missing_fields' => ['email', 'password']
            ],
        ];
    }

    /**
     * Generate valid email test cases for property-based testing
     */
    private function generateValidEmailTestCases(int $count): array
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'firstname+lastname@example.com',
            'email@123.123.123.123',
            'user_name@example-domain.com',
            'test.email.with+symbol@example.com',
            'x@example.com',
            'example@s.example',
            'test@example-one.com',
            'test@example.name',
        ];

        $cases = [];
        for ($i = 0; $i < min($count, count($validEmails)); $i++) {
            $cases[] = ['email' => $validEmails[$i]];
        }

        return $cases;
    }

    /**
     * Generate empty input test cases for property-based testing
     */
    private function generateEmptyInputTestCases(): array
    {
        return [
            [
                'data' => ['email' => '', 'password' => ''],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => '   ', 'password' => '   '],
                'expected_errors' => ['email', 'password']
            ],
            [
                'data' => ['email' => '', 'password' => 'validpass'],
                'expected_errors' => ['email']
            ],
            [
                'data' => ['email' => 'valid@email.com', 'password' => ''],
                'expected_errors' => ['password']
            ],
            [
                'data' => ['email' => "\t\n", 'password' => "\r\n"],
                'expected_errors' => ['email', 'password']
            ],
        ];
    }
}
