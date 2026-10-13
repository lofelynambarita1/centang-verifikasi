@extends('layouts.guest')

@section('title', 'Masuk | Verifikasi ISPO')

@section('content')
    <style>
        /* Styling Khusus Halaman Login */
        .login-card-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .login-card-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 0.375rem 0;
            letter-spacing: -0.01em;
        }

        .login-card-header p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            box-sizing: border-box;
            transition: all 0.15s ease-in-out;
        }

        .input-control:hover {
            border-color: #94a3b8;
        }

        .input-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        /* Password Toggle Button */
        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: color 0.15s ease;
        }

        .password-toggle-btn:hover {
            color: #0f172a;
        }

        /* Form Error Box */
        .form-alert-error {
            display: flex;
            align-items: flex-start;
            gap: 0.625rem;
            padding: 0.75rem 1rem;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.5rem;
            color: #dc2626;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            line-height: 1.4;
        }

        .form-alert-error svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Remember Me Section */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: #059669;
            border-radius: 0.25rem;
            cursor: pointer;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6875rem 1.25rem;
            background-color: #059669;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-submit:hover {
            background-color: #047857;
        }

        .btn-submit:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 2px;
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Spinner Animation */
        .btn-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Demo Account Box */
        .demo-info-card {
            margin-top: 1.75rem;
            padding: 0.875rem 1rem;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.5rem;
            font-size: 0.78125rem;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }

        .demo-info-card strong {
            color: #334155;
        }
    </style>

    <div class="login-card-header">
        <h2>Selamat Datang Kembali</h2>
        <p>Silakan masuk untuk mengakses sistem Verifikasi ISPO</p>
    </div>

    @if ($errors->any())
        <div class="form-alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form class="login-form" id="login-form" method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group">
            <label for="email">Alamat Email</label>
            <div class="input-wrapper">
                <input 
                    id="email" 
                    class="input-control" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="password">Kata Sandi</label>
            <div class="input-wrapper">
                <input 
                    id="password" 
                    class="input-control" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi Anda"
                    style="padding-right: 2.5rem;"
                >
                <button 
                    type="button" 
                    class="password-toggle-btn" 
                    id="toggle-password" 
                    aria-label="Tampilkan kata sandi"
                    title="Tampilkan/Sembunyikan kata sandi"
                >
                    <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-options">
            <label class="checkbox-label">
                <input type="checkbox" name="remember" value="1">
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <button class="btn-submit" type="submit" id="submit-btn">
            <span class="btn-spinner" id="btn-spinner" aria-hidden="true"></span>
            <span id="btn-text">Masuk ke Akun</span>
        </button>
    </form>

    <div class="demo-info-card">
        💡 <strong>Akun Demo (Seeder):</strong><br>
        <code>admin@example.com</code> / <code>auditor1@example.com</code><br>
        Kata sandi: <strong>password</strong>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Password Visibility Toggle Logic
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('toggle-password');
            const eyeIcon = document.getElementById('eye-icon');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    togglePasswordBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');

                    // Toggle Icon (Eye / Eye-off)
                    if (isPassword) {
                        eyeIcon.innerHTML = `
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        `;
                    } else {
                        eyeIcon.innerHTML = `
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        `;
                    }
                });
            }

            // Form Submit Loading Feedback
            const loginForm = document.getElementById('login-form');
            const submitBtn = document.getElementById('submit-btn');
            const btnSpinner = document.getElementById('btn-spinner');
            const btnText = document.getElementById('btn-text');

            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function () {
                    submitBtn.disabled = true;
                    if (btnSpinner) btnSpinner.style.display = 'inline-block';
                    if (btnText) btnText.textContent = 'Memproses...';
                });
            }
        });
    </script>
@endsection