<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
        ]);
    }

    /** @test */
    public function 会員登録後、認証メールが送信される()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',

        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@test.com',
        ]);

        $user = User::first();
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo(
            [$user],
            VerifyEmail::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }

    /** @test */
    public function メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/email/verify');

        $verifyView = $response->getContent();

        preg_match('/http:\/\/localhost:8025/', $verifyView, $href);
        $this->assertSame('http://localhost:8025', $href[0]);
    }

    /** @test */
    public function メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class, function ($notification, $channels) use (&$url, $user) {
            $mail = $notification->toMail($user);
            $url = $mail->actionUrl;

            $this->assertStringContainsString('email/verify/' . $user->id, $url);

            return true;
        });

        $response = $this->actingAs($user)->get($url);

        $response->assertRedirect('/attendance?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
