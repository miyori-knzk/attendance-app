<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/login', [
            'email' => 'test@test',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function 登録内容と一致しない場合、バリデーションメッセージが表示される()
    {
        User::factory()->create();

        $response = $this->post('/login', [
            'email' => 'test@test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
