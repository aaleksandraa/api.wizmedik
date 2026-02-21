<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Storage Operations Preservation Property Tests
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 *
 * IMPORTANT: These tests follow observation-first methodology.
 * They verify that non-buggy storage operations work correctly on UNFIXED code.
 *
 * These tests are EXPECTED TO PASS on unfixed code - they capture baseline behavior
 * that must be preserved after implementing the permission fix.
 *
 * Property 2: Preservation - Other Storage Operations Unchanged
 * For any file system operation that does NOT involve Plesk user creating files
 * in storage/framework/, the system SHALL continue to work identically.
 *
 * Test Coverage:
 * - Log file creation in storage/logs/
 * - Cache operations in storage/framework/cache/ (as web server user)
 * - Session operations in storage/framework/sessions/ (as web server user)
 * - View cache in storage/framework/views/ (as web server user)
 * - File uploads in storage/app/public/
 * - Bootstrap cache operations in bootstrap/cache/
 */
class StorageOperationsPreservationTest extends TestCase
{
    protected array $testFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFiles = [];
    }

    protected function tearDown(): void
    {
        // Clean up all test files created during tests
        foreach ($this->testFiles as $file) {
            if (File::exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    /**
     * Track a test file for cleanup
     */
    protected function trackFile(string $path): void
    {
        $this->testFiles[] = $path;
    }

    /**
     * Property Test: Log File Creation Preservation
     *
     * Validates: Requirement 3.1
     * WHEN Laravel aplikacija kreira log fajlove u storage/logs/ direktorijumu
     * THEN sistem SHALL CONTINUE TO uspješno kreirati i pisati u log fajlove
     *
     * This test verifies that Laravel can create and write to log files.
     * This behavior must remain unchanged after the permission fix.
     *
     * @dataProvider logFileOperationsProvider
     */
    public function test_log_file_creation_preservation(string $channel, string $message): void
    {
        // Observe behavior on unfixed code: Laravel should be able to write logs
        Log::channel($channel)->info($message);

        // Verify the log file was created and contains our message
        $logPath = storage_path('logs/laravel.log');
        $this->assertFileExists($logPath, "Log file should be created in storage/logs/");

        $logContent = File::get($logPath);
        $this->assertStringContainsString(
            $message,
            $logContent,
            "Log file should contain the logged message"
        );
    }

    /**
     * Property Test: Cache File Creation Preservation
     *
     * Validates: Requirement 3.2
     * WHEN Laravel aplikacija kreira cache fajlove u storage/framework/cache/ direktorijumu
     * THEN sistem SHALL CONTINUE TO uspješno kreirati i pisati u cache fajlove
     *
     * This test verifies that Laravel can create and write cache files.
     * Cache operations are performed by the web server user, not Plesk user.
     *
     * @dataProvider cacheOperationsProvider
     */
    public function test_cache_file_creation_preservation(string $key, $value, int $ttl): void
    {
        // Observe behavior on unfixed code: Laravel should be able to cache data
        Cache::put($key, $value, $ttl);

        // Verify the cache was stored successfully
        $this->assertTrue(
            Cache::has($key),
            "Cache key '{$key}' should exist after Cache::put()"
        );

        $this->assertEquals(
            $value,
            Cache::get($key),
            "Cache should return the stored value"
        );

        // Clean up
        Cache::forget($key);
    }

    /**
     * Property Test: Session File Creation Preservation
     *
     * Validates: Requirement 3.3
     * WHEN Laravel aplikacija kreira session fajlove u storage/framework/sessions/ direktorijumu
     * THEN sistem SHALL CONTINUE TO uspješno kreirati i pisati u session fajlove
     *
     * This test verifies that Laravel can create and write session files.
     * Session operations are performed by the web server user, not Plesk user.
     *
     * @dataProvider sessionOperationsProvider
     */
    public function test_session_file_creation_preservation(string $key, $value): void
    {
        // Observe behavior on unfixed code: Laravel should be able to store session data
        Session::put($key, $value);

        // Verify the session data was stored successfully
        $this->assertTrue(
            Session::has($key),
            "Session key '{$key}' should exist after Session::put()"
        );

        $this->assertEquals(
            $value,
            Session::get($key),
            "Session should return the stored value"
        );

        // Clean up
        Session::forget($key);
    }

    /**
     * Property Test: View Cache File Creation Preservation
     *
     * Validates: Requirement 3.4
     * WHEN Laravel aplikacija kreira view cache fajlove u storage/framework/views/ direktorijumu
     * THEN sistem SHALL CONTINUE TO uspješno kreirati i pisati u view cache fajlove
     *
     * This test verifies that Laravel can compile and cache Blade views.
     * View cache operations are performed by the web server user, not Plesk user.
     *
     * @dataProvider viewCacheOperationsProvider
     */
    public function test_view_cache_creation_preservation(string $viewName, array $data): void
    {
        // Create a temporary test view
        $viewPath = resource_path("views/test_{$viewName}.blade.php");
        $viewContent = '<div>{{ $message }}</div>';
        File::put($viewPath, $viewContent);
        $this->trackFile($viewPath);

        // Observe behavior on unfixed code: Laravel should be able to compile views
        $rendered = view("test_{$viewName}", $data)->render();

        // Verify the view was rendered successfully
        $this->assertStringContainsString(
            $data['message'],
            $rendered,
            "View should be compiled and rendered with data"
        );

        // Verify view cache directory exists and is writable
        $viewCachePath = storage_path('framework/views');
        $this->assertDirectoryExists($viewCachePath, "View cache directory should exist");
        $this->assertDirectoryIsWritable($viewCachePath, "View cache directory should be writable");
    }

    /**
     * Property Test: File Upload Preservation
     *
     * Validates: Requirement 3.5
     * WHEN korisnik upload-uje fajlove kroz aplikaciju u storage/app/public/ direktorijum
     * THEN sistem SHALL CONTINUE TO uspješno čuvati upload-ovane fajlove
     *
     * This test verifies that file uploads work correctly.
     *
     * @dataProvider fileUploadOperationsProvider
     */
    public function test_file_upload_preservation(string $filename, string $content): void
    {
        // Observe behavior on unfixed code: Laravel should be able to store uploaded files
        $path = Storage::disk('public')->put($filename, $content);

        // Verify the file was stored successfully
        $this->assertNotFalse($path, "File should be stored successfully");
        $this->assertTrue(
            Storage::disk('public')->exists($path),
            "Uploaded file should exist in storage/app/public/"
        );

        $this->assertEquals(
            $content,
            Storage::disk('public')->get($path),
            "Uploaded file should contain the correct content"
        );

        // Clean up
        Storage::disk('public')->delete($path);
    }

    /**
     * Property Test: Bootstrap Cache File Creation Preservation
     *
     * Validates: Requirement 3.6
     * WHEN Laravel aplikacija kreira cache fajlove u bootstrap/cache/ direktorijumu
     * THEN sistem SHALL CONTINUE TO uspješno kreirati i pisati u cache fajlove
     *
     * This test verifies that Laravel can create bootstrap cache files.
     *
     * @dataProvider bootstrapCacheOperationsProvider
     */
    public function test_bootstrap_cache_creation_preservation(string $cacheFile, string $content): void
    {
        $cachePath = base_path("bootstrap/cache/{$cacheFile}");

        // Observe behavior on unfixed code: Laravel should be able to write bootstrap cache
        $result = File::put($cachePath, $content);
        $this->trackFile($cachePath);

        // Verify the cache file was created successfully
        $this->assertNotFalse($result, "Bootstrap cache file should be created");
        $this->assertFileExists($cachePath, "Bootstrap cache file should exist");

        $this->assertEquals(
            $content,
            File::get($cachePath),
            "Bootstrap cache file should contain the correct content"
        );
    }

    /**
     * Property Test: Multiple Storage Operations Preservation
     *
     * This test verifies that multiple storage operations can be performed
     * in sequence without interference. This ensures the system maintains
     * consistent behavior across different storage subsystems.
     */
    public function test_multiple_storage_operations_preservation(): void
    {
        // Test 1: Log operation
        Log::info('Preservation test: multiple operations');
        $this->assertFileExists(storage_path('logs/laravel.log'));

        // Test 2: Cache operation
        Cache::put('preservation_test_key', 'test_value', 60);
        $this->assertTrue(Cache::has('preservation_test_key'));

        // Test 3: Session operation
        Session::put('preservation_test_session', 'session_value');
        $this->assertTrue(Session::has('preservation_test_session'));

        // Test 4: File upload
        $uploadPath = Storage::disk('public')->put('preservation_test.txt', 'test content');
        $this->assertTrue(Storage::disk('public')->exists($uploadPath));

        // Clean up
        Cache::forget('preservation_test_key');
        Session::forget('preservation_test_session');
        Storage::disk('public')->delete($uploadPath);
    }

    /**
     * Data provider for log file operations
     * Generates multiple test cases to simulate property-based testing
     */
    public static function logFileOperationsProvider(): array
    {
        return [
            'simple log message' => ['stack', 'Preservation test: simple log message'],
            'log with special characters' => ['stack', 'Test: special chars !@#$%^&*()'],
            'log with unicode' => ['stack', 'Test: unicode characters čćžšđ'],
            'long log message' => ['stack', str_repeat('Long message ', 50)],
            'log with newlines' => ['stack', "Multi\nline\nlog\nmessage"],
        ];
    }

    /**
     * Data provider for cache operations
     * Generates multiple test cases with different data types
     */
    public static function cacheOperationsProvider(): array
    {
        return [
            'string value' => ['test_string_key', 'test string value', 60],
            'integer value' => ['test_int_key', 12345, 60],
            'array value' => ['test_array_key', ['foo' => 'bar', 'baz' => 'qux'], 60],
            'boolean value' => ['test_bool_key', true, 60],
            'null value' => ['test_null_key', null, 60],
            'large array' => ['test_large_key', array_fill(0, 100, 'data'), 60],
        ];
    }

    /**
     * Data provider for session operations
     * Generates multiple test cases with different data types
     */
    public static function sessionOperationsProvider(): array
    {
        return [
            'string session' => ['test_session_string', 'session value'],
            'integer session' => ['test_session_int', 42],
            'array session' => ['test_session_array', ['key' => 'value']],
            'nested array session' => ['test_session_nested', ['level1' => ['level2' => 'value']]],
            'boolean session' => ['test_session_bool', false],
        ];
    }

    /**
     * Data provider for view cache operations
     * Generates multiple test cases with different view scenarios
     */
    public static function viewCacheOperationsProvider(): array
    {
        return [
            'simple view' => ['simple', ['message' => 'Hello World']],
            'view with special chars' => ['special', ['message' => 'Special: !@#$%']],
            'view with unicode' => ['unicode', ['message' => 'Unicode: čćžšđ']],
            'view with long content' => ['long', ['message' => str_repeat('Content ', 100)]],
        ];
    }

    /**
     * Data provider for file upload operations
     * Generates multiple test cases with different file types
     */
    public static function fileUploadOperationsProvider(): array
    {
        return [
            'text file' => ['test.txt', 'Simple text content'],
            'json file' => ['test.json', '{"key": "value"}'],
            'csv file' => ['test.csv', "name,email\nJohn,john@example.com"],
            'large file' => ['large.txt', str_repeat('Large content ', 1000)],
            'file with special chars' => ['special-čćžšđ.txt', 'Content with special chars'],
        ];
    }

    /**
     * Data provider for bootstrap cache operations
     * Generates multiple test cases for bootstrap cache files
     */
    public static function bootstrapCacheOperationsProvider(): array
    {
        return [
            'config cache' => ['test_config.php', '<?php return ["key" => "value"];'],
            'routes cache' => ['test_routes.php', '<?php // Routes cache'],
            'services cache' => ['test_services.php', '<?php return [];'],
        ];
    }
}
