<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class EnvExampleTest extends TestCase
{
    /** @return list<string> */
    private function keys(): array
    {
        preg_match_all('/^#?\s*([A-Z][A-Z0-9_]*)=/m', file_get_contents(base_path('.env.example')), $matches);

        return array_values(array_unique($matches[1]));
    }

    public function test_env_example_documents_every_variable_the_project_adds(): void
    {
        $keys = $this->keys();

        foreach ([
            'APP_NAME', 'APP_ENV', 'APP_KEY', 'APP_DEBUG', 'APP_URL', 'APP_LOCALE', 'APP_TIMEZONE',
            'DB_CONNECTION', 'FILESYSTEM_DISK', 'PHOTOS_DISK', 'QUEUE_CONNECTION', 'CARS_PER_PAGE',
            'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET', 'AWS_URL', 'AWS_ENDPOINT',
            'SESSION_SECURE_COOKIE',
        ] as $required) {
            $this->assertContains($required, $keys, "{$required} is missing from .env.example");
        }
    }

    public function test_env_example_does_not_keep_contact_data_or_secrets(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        foreach (['417-4036', '19784174036', 'Merrimack', 'password=', 'secret_key'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $env);
        }
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $env);
        $this->assertMatchesRegularExpression('/^QUEUE_CONNECTION=sync$/m', $env);
    }

    public function test_the_project_uses_no_queue_tables(): void
    {
        $this->assertSame('sync', config('queue.default'));
        $this->assertFileDoesNotExist(database_path('migrations/0001_01_01_000002_create_jobs_table.php'));
    }
}
