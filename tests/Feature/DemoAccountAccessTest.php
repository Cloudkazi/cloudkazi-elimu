<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoAccountMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DemoAccountAccessTest extends TestCase
{
    public function test_demo_account_requests_are_blocked_for_write_actions(): void
    {
        $role = Role::firstOrCreate(['slug' => Role::SCHOOL_ADMIN], [
            'name' => 'School Admin',
            'description' => 'School IT Administrator & System Manager',
        ]);

        $user = User::create([
            'name' => 'Demo Freeze',
            'email' => 'demo.freeze@example.com',
            'phone' => '+254700000777',
            'password' => bcrypt('Password@123!'),
            'role_id' => $role->id,
            'school_id' => null,
            'is_active' => false,
            'is_demo' => true,
        ]);

        Auth::login($user);

        $request = Request::create('/students', 'POST', ['name' => 'Jane']);
        $request->setLaravelSession($this->app['session.store']);

        $middleware = new DemoAccountMiddleware();
        $response = $middleware->handle($request, function () {
            return response('OK', Response::HTTP_OK);
        });

        $this->assertNotSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location', ''), '/login') || str_contains($response->getContent(), 'demo'));
    }
}
