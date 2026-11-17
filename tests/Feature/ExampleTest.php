<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_login_redirect_is_reachable(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
