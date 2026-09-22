@extends('layouts.guest')

@section('title', 'Masuk | Collaborative Audit Report')
@section('content')
    @if ($errors->any())
        <div class="form-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form class="form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field field--wide">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="field field--wide">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
        </div>

        <label class="field" style="flex-direction: row; align-items: center; gap: 8px; font-size: 14px; font-weight: 600;">
            <input type="checkbox" name="remember" value="1" style="width: 16px; height: 16px;">
            Ingat saya
        </label>

        <button class="btn btn--primary btn--block" type="submit">Masuk</button>
    </form>

    <p style="margin-top: 22px; font-size: 12.5px; color: var(--quiet); text-align: center; line-height: 1.6;">
        Akun demo tersedia lewat seeder: admin@example.com / auditor1@example.com dkk.<br>
        Kata sandi: <strong>password</strong>
    </p>
@endsection