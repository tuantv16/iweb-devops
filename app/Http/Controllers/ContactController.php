<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public const TOPICS = [
        'hoi-dap' => 'Hỏi đáp',
        'hop-tac' => 'Hợp tác',
        'gop-y' => 'Góp ý',
    ];

    public function create(): View
    {
        return view('contact', ['topics' => self::TOPICS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'regex:/^[0-9+\s().-]{8,20}$/'],
            'topic' => ['required', Rule::in(array_keys(self::TOPICS))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'required' => ':attribute không được để trống.',
            'email' => ':attribute không đúng định dạng.',
            'max' => ':attribute quá dài (tối đa :max ký tự).',
            'message.min' => ':attribute quá ngắn (tối thiểu :min ký tự).',
            'phone.regex' => ':attribute không hợp lệ.',
            'topic.in' => ':attribute không hợp lệ.',
        ], [
            'name' => 'Họ tên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'topic' => 'Chủ đề',
            'message' => 'Nội dung',
        ]);

        // Log chỉ ghi tên trường lỗi + thông báo lỗi, KHÔNG ghi giá trị người dùng nhập (tránh lộ dữ liệu cá nhân vào log).
        if ($validator->fails()) {
            Log::warning('contact.validation_failed', [
                'ip' => $request->ip(),
                'failed_fields' => $validator->errors()->keys(),
                'errors' => $validator->errors()->toArray(),
            ]);

            return redirect()->route('contact.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        Log::info('contact.submitted', [
            'ip' => $request->ip(),
            'name' => $data['name'],
            'topic' => $data['topic'],
            'has_phone' => filled($data['phone'] ?? null),
            'message_length' => mb_strlen($data['message']),
        ]);

        return redirect()->route('contact.create')
            ->with('success', 'Đã gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm.');
    }
}
