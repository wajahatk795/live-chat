<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use App\Http\Controllers\BrandController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandInstallScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_script_uses_app_url_and_only_the_brand_query_parameter(): void
    {
        $admin = User::create([
            'name' => 'Brand admin',
            'email' => 'brand-install-admin@example.com',
            'password' => 'password',
            'role' => 1,
        ]);
        $brand = Brand::create([
            'name' => 'Widget brand',
            'url' => 'http://example.test',
            'domain' => 'example.test',
            'status' => 1,
            'verify_token' => 'must-not-appear-in-embed',
        ]);

        $this->actingAs($admin);
        foreach ([
            'http://localhost/live-chat/public/' => 'http://localhost/live-chat/public',
            'https://chat.example.com/live-chat/public' => 'https://chat.example.com/live-chat/public',
        ] as $appUrl => $expectedBase) {
            config(['app.url' => $appUrl]);
            $view = app(BrandController::class)->install($brand->id);
            $expected = '<!--Start of Live Chat -->
    <script src="' . $expectedBase . '/widget.js?brand=' . $brand->id . '"></script>
    <!-- End of Live Chat -->';

            $normalizeLines = fn (string $value) => str_replace(["\r\n", "\r"], "\n", $value);
            $this->assertSame($normalizeLines($expected), $normalizeLines($view->getData()['script']));
        }
    }
}
