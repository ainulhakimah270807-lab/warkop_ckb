<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileServerTest extends TestCase
{
    public function test_public_server_probe_contains_only_protocol_and_instance_identity(): void
    {
        $this->getJson('/api/mobile/server-info')->assertOk()->assertExactJson([
            'app' => 'warkop-ckb',
            'protocol' => 1,
            'instance_id' => hash('sha256', (string) config('app.key')),
        ]);
    }

    public function test_https_forwarding_is_trusted_only_from_local_proxy(): void
    {
        foreach (['127.0.0.1' => true, '192.168.1.50' => false] as $ip => $secure) {
            $request = Request::create('http://localhost/api/mobile/server-info', 'GET', [], [], [], [
                'REMOTE_ADDR' => $ip,
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'contoh.ngrok-free.app',
            ]);
            (new TrustProxies())->handle($request, function ($request) use ($secure) {
                $this->assertSame($secure, $request->isSecure());
                return response('ok');
            });
        }
    }

    public function test_web_client_can_preflight_api_and_menu_images_with_ngrok_header(): void
    {
        foreach (['/api/mobile/server-info', '/menu-images/1'] as $path) {
            $this->withHeaders([
                'Origin' => 'http://localhost:8080',
                'Access-Control-Request-Method' => 'GET',
                'Access-Control-Request-Headers' => 'ngrok-skip-browser-warning',
            ])->options($path)->assertNoContent()
                ->assertHeader('Access-Control-Allow-Origin', '*')
                ->assertHeader('Access-Control-Allow-Headers', 'ngrok-skip-browser-warning');
        }
    }
}
