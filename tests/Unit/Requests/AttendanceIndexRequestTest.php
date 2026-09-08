<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\AttendanceIndexRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttendanceIndexRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new AttendanceIndexRequest;

        return Validator::make($data, $request->rules());
    }

    /** @test */
    public function dateが_nul_lでもバリデーション通過(): void
    {
        $validator = $this->validator(['date' => '']);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function date項目が_ｙ－ｍ－ｄでバリデーション通過(): void
    {
        $validator = $this->validator(['date' => '2025-09-01']);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function date項目が_ｙ－ｍ－ｄでないとバリデーションエラー(): void
    {
        $validator = $this->validator(['date' => '2025/09/01']);

        $this->assertTrue($validator->fails());
    }
}
