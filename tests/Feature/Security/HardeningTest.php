<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_have_csp_nonce_and_security_headers(): void
    {
        $response = $this->get(route('login'))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertMatchesRegularExpression("/nonce-([A-Za-z0-9]+)'/", $policy);
        preg_match("/nonce-([A-Za-z0-9]+)'/", $policy, $matches);
        $this->assertStringContainsString('nonce="'.$matches[1].'"', $response->getContent());
    }

    public function test_production_redirects_http_and_adds_hsts_only_after_https(): void
    {
        $this->app['env'] = 'production';
        $this->get('/login')->assertRedirect('https://127.0.0.1/login');

        $this->withServerVariables(['HTTPS' => 'on'])->get('/login')
            ->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_authenticated_html_is_not_stored_by_the_browser(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('home'))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_session_cookie_defaults_are_http_only_and_same_site_lax(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_external_scheduler_endpoint_requires_a_bearer_secret_and_runs_the_laravel_scheduler(): void
    {
        $this->app['env'] = 'production';
        config(['services.scheduler.secret' => 'local-test-scheduler-key']);
        $this->withServerVariables(['HTTPS' => 'on'])->postJson(route('scheduler.run'))->assertNotFound();

        Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
        $this->withServerVariables(['HTTPS' => 'on'])->withHeaders(['Authorization' => 'Bearer local-test-scheduler-key'])
            ->postJson(route('scheduler.run'))->assertOk()->assertJson(['status' => 'schedule completed']);
    }
}
