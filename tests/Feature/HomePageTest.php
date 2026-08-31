<?php

namespace Tests\Feature;

use App\Http\Controllers\Shop\HomeController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_route_points_to_shop_home_controller(): void
    {
        $route = Route::getRoutes()->getByName('home');

        $this->assertNotNull($route);
        $this->assertSame(HomeController::class, $route->getControllerClass());
        $this->assertSame('index', $route->getActionMethod());
    }
}
