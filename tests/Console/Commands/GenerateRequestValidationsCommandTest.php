<?php

namespace RequestValidationGenerator\Tests\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class GenerateRequestValidationsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for migrations
        File::makeDirectory(database_path('migrations/temp'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Remove the temporary directory for migrations
        File::deleteDirectory(database_path('migrations/temp'));
    }

    /** @test */
    public function it_generates_request_validation_files_for_migrations()
    {
        // Copy your migration files to the temporary directory
        File::copyDirectory(database_path('migrations'), database_path('migrations/temp'));

        // Run the generate:request-validations command
        Artisan::call('generate:request-validations');

        // Assert that the request validation files were generated
        $this->assertGeneratedRequestValidationFiles();
    }

    protected function assertGeneratedRequestValidationFiles()
    {
        // Assert that the request validation files were generated for your migrations
        // Modify this assertion as per your specific migrations and generated files

        $this->assertTrue(File::exists(app_path('Http/Requests/ExampleTable/Request.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/AnotherTable/Request.php')));
        $this->assertTrue(File::exists(app_path('Http/Requests/YetAnotherTable/Request.php')));
    }
}
