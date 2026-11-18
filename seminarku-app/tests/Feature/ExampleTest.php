<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_public_pages_return_successful_response(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/events')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
    }
}