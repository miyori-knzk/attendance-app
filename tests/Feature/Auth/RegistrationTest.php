<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 名前が未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@test',
            'password' => 'password',
            'password_confirmation' => 'password',

        ]);

        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',

        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワードが8文字未満の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test',
            'password' => 'pass',
            'password_confirmation' => 'pass',

        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワードが一致しない場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test',
            'password' => 'pass',
            'password_confirmation' => 'passw',

        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test',
            'password' => '',
            'password_confirmation' => 'passw',

        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function フォームに内容が入力されていた場合、データが正常に保存される()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@test',
            'password' => 'password',
            'password_confirmation' => 'password',

        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@test',
        ]);

        $response->assertRedirect('/attendance');

    }
}
