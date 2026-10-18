@extends('layouts.site')

@section('title', config('app.name').' - Liên hệ')

@section('content')
<div class="page-head reveal">
    <h1><span class="grad">Liên hệ</span></h1>
    <p>Để lại lời nhắn, tôi sẽ phản hồi sớm nhất có thể.</p>
</div>

@if (session('success'))
    <div class="alert alert-ok" role="status">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-err" role="alert">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
@endif

<form class="panel reveal d1" method="POST" action="{{ route('contact.store') }}" novalidate>
    @csrf

    <label for="name">Họ tên</label>
    <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" class="@error('name') invalid @enderror">
    @error('name') <p class="field-error">{{ $message }}</p> @enderror

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" class="@error('email') invalid @enderror">
    @error('email') <p class="field-error">{{ $message }}</p> @enderror

    <label for="phone">Số điện thoại <span class="opt">(không bắt buộc)</span></label>
    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" class="@error('phone') invalid @enderror">
    @error('phone') <p class="field-error">{{ $message }}</p> @enderror

    <label for="topic">Chủ đề</label>
    <select id="topic" name="topic" class="@error('topic') invalid @enderror">
        <option value="">-- Chọn chủ đề --</option>
        @foreach ($topics as $value => $label)
            <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('topic') <p class="field-error">{{ $message }}</p> @enderror

    <label for="message">Nội dung</label>
    <textarea id="message" name="message" rows="5" maxlength="2000" class="@error('message') invalid @enderror">{{ old('message') }}</textarea>
    @error('message') <p class="field-error">{{ $message }}</p> @enderror

    <button class="btn btn-primary" type="submit">Gửi liên hệ</button>
</form>
@endsection
