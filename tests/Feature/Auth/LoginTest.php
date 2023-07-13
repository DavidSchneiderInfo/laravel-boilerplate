<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Tests\Feature\MakesRequestsFromPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase, MakesRequestsFromPage;

    protected function successfulLoginRoute()
    {
        return route('home');
    }

    protected function loginGetRoute()
    {
        return route('login');
    }

    protected function loginPostRoute()
    {
        return route('login');
    }

    protected function logoutRoute()
    {
        return route('logout');
    }

    protected function successfulLogoutRoute()
    {
        return '/';
    }

    protected function guestMiddlewareRoute()
    {
        return route('home');
    }

    public function testUserCanViewALoginForm()
    {
        $response = $this->get($this->loginGetRoute());

        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    public function testUserCannotViewALoginFormWhenAuthenticated()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get($this->loginGetRoute());

        $response->assertRedirect($this->guestMiddlewareRoute());
    }

    public function testUserCanLoginWithCorrectCredentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);

        $response = $this->post($this->loginPostRoute(), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect($this->successfulLoginRoute());
        $this->assertAuthenticatedAs($user);
    }

    public function testUserCannotLoginWithIncorrectPassword()
    {
        $user = User::factory()->create([
            'password' => bcrypt('i-love-laravel'),
        ]);

        $response = $this->fromPage($this->loginGetRoute())->post($this->loginPostRoute(), [
            'email' => $user->email,
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect($this->loginGetRoute());
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testUserCannotLoginWithEmailThatDoesNotExist()
    {
        $response = $this->fromPage($this->loginGetRoute())->post($this->loginPostRoute(), [
            'email' => 'nobody@example.com',
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect($this->loginGetRoute());
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testUserCanLogout()
    {
        $this->be(User::factory()->create());

        $response = $this->post($this->logoutRoute());

        $response->assertRedirect($this->successfulLogoutRoute());
        $this->assertGuest();
    }

    public function testUserCannotLogoutWhenNotAuthenticated()
    {
        $response = $this->post($this->logoutRoute());

        $response->assertRedirect($this->successfulLogoutRoute());
        $this->assertGuest();
    }

    public function testUserCannotMakeMoreThanFiveAttemptsInOneMinute()
    {
        $user = User::factory()->create([
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);

        foreach (range(0, 5) as $_) {
            $response = $this->fromPage($this->loginGetRoute())->post($this->loginPostRoute(), [
                'email' => $user->email,
                'password' => 'invalid-password',
            ]);
        }

        $response->assertRedirect($this->loginGetRoute());
        $response->assertSessionHasErrors('email');
        $this->assertMatchesRegularExpression('/Too many login attempts/', collect(
            $response->baseResponse
                ->getSession()
                ->get('errors')
                ->getBag('default')
                ->get('email')
                )->first()
        );
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }
}
