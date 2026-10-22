<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvExampleTest extends TestCase
{
    public function test_env_example_targets_mysql_and_redis(): void
    {
        $env = collect(file(base_path('.env.example'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->reject(fn (string $line) => str_starts_with($line, '#') || ! str_contains($line, '='))
            ->mapWithKeys(function (string $line) {
                [$key, $value] = explode('=', $line, 2);

                return [$key => $value];
            });

        $this->assertSame('mysql', $env['DB_CONNECTION']);
        $this->assertSame('redis', $env['CACHE_STORE']);
        $this->assertSame('redis', $env['SESSION_DRIVER']);
        $this->assertSame('redis', $env['QUEUE_CONNECTION']);
    }
}
