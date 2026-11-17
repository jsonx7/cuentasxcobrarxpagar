<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HttpsProxyTest extends TestCase
{
    #[Test]
    public function los_assets_se_generan_con_https_detras_del_proxy(): void
    {
        // En producción no se define ASSET_URL: los assets usan el esquema del request.
        config(['app.asset_url' => null]);

        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'prestamos.example.com',
            'X-Forwarded-Port' => '443',
        ])->get('/login');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('https://prestamos.example.com/build/', $html);
        $this->assertStringNotContainsString('http://prestamos.example.com/build/', $html);
    }
}
