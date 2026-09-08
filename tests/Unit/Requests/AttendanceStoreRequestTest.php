<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\AttendanceStoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttendanceStoreRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new AttendanceStoreRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function actionがclock_inであればバリデーション通過(): void
    {
        $validator = $this->validator(['action' => 'clock_in']);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function actionが許可されていない文字列であればバリデーションエラー(): void
    {
        $validator = $this->validator(['action' => 'attendance_in']);

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function actionが空文字であればバリデーションエラー(): void
    {
        $validator = $this->validator(['action' => '']);

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function actionがなければバリデーションエラー(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->fails());
    }
}
