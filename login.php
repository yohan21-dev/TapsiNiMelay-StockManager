<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (attemptLogin($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>Login — Tapsi Stock</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* =========================================================
           LOGIN PAGE
        ========================================================= */

        .login-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(37, 99, 235, 0.12),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 90% 90%,
                    rgba(59, 130, 246, 0.10),
                    transparent 35%
                ),
                #f4f6f8;
        }

        /* Decorative background circles */

        .login-page::before,
        .login-page::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .login-page::before {
            width: 320px;
            height: 320px;
            top: -180px;
            right: -120px;
            background: rgba(37, 99, 235, 0.08);
        }

        .login-page::after {
            width: 260px;
            height: 260px;
            bottom: -150px;
            left: -100px;
            background: rgba(59, 130, 246, 0.07);
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 42px 40px 38px;
            position: relative;
            z-index: 1;

            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(229, 231, 235, 0.9);
            border-radius: 24px;

            box-shadow:
                0 20px 50px rgba(15, 23, 42, 0.10),
                0 4px 12px rgba(15, 23, 42, 0.04);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        /* Logo */

        .login-logo {
            width: 82px;
            height: 82px;
            margin: 0 auto 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 22px;
            overflow: hidden;

            background: #ffffff;
            border: 1px solid #e5e7eb;

            box-shadow:
                0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .logo-placeholder {
            display: none;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;

            font-size: 24px;
            font-weight: 800;
            color: #ffffff;

            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );
        }

        /* Heading */

        .login-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-heading h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #111827;
        }

        .login-heading p {
            margin: 9px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Error */

        .login-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            margin-bottom: 20px;
            padding: 13px 14px;

            border-radius: 12px;

            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;

            font-size: 13px;
            line-height: 1.45;
        }

        .login-error-icon {
            flex-shrink: 0;
            font-size: 15px;
            line-height: 1.3;
        }

        /* Form */

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 19px;
        }

        .login-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .login-field label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input {
            width: 100%;
            height: 48px;

            padding: 0 14px;

            border: 1px solid #d1d5db;
            border-radius: 12px;

            background: #ffffff;
            color: #111827;

            font-size: 15px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .login-input::placeholder {
            color: #9ca3af;
        }

        .login-input:hover {
            border-color: #9ca3af;
        }

        .login-input:focus {
            border-color: #2563eb;
            background: #ffffff;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .password-input {
            padding-right: 48px;
        }

        /* Password toggle */

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 6px;

            width: 36px;
            height: 36px;

            transform: translateY(-50%);

            display: flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 9px;

            background: transparent;
            color: #6b7280;

            cursor: pointer;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }

        .password-toggle:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .password-toggle:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
        }

        /* Button */

        .login-button {
            width: 100%;
            height: 50px;

            margin-top: 4px;

            border: 0;
            border-radius: 12px;

            background: linear-gradient(
                135deg,
                #2563eb,
                #1d4ed8
            );

            color: #ffffff;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 7px 16px rgba(37, 99, 235, 0.22);

            transition:
                transform 0.15s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .login-button:hover {
            filter: brightness(1.04);
            box-shadow:
                0 9px 20px rgba(37, 99, 235, 0.28);
        }

        .login-button:active {
            transform: translateY(1px);
            box-shadow:
                0 4px 10px rgba(37, 99, 235, 0.20);
        }

        .login-button:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.22);
            outline-offset: 2px;
        }

        /* Footer */

        .login-footer {
            margin-top: 28px;

            text-align: center;

            color: #9ca3af;
            font-size: 12px;
        }

        /* Mobile */

        @media (max-width: 480px) {
            .login-page {
                padding: 16px;
            }

            .login-card {
                padding: 34px 24px 28px;
                border-radius: 20px;
            }

            .login-logo {
                width: 72px;
                height: 72px;
                border-radius: 19px;
                margin-bottom: 18px;
            }

            .login-heading {
                margin-bottom: 26px;
            }

            .login-heading h1 {
                font-size: 25px;
            }

            .login-heading p {
                font-size: 13px;
            }

            .login-input {
                height: 47px;
            }

            .login-button {
                height: 49px;
            }
        }

        /* Reduce motion */

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <main class="login-card">

        <div class="login-logo">
            <img
                src="assets/img/logo.jpg"
                alt="Tapsi Ni Melay logo"
                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='flex';
                "
            >

            <span class="logo-placeholder" aria-hidden="true">
                TS
            </span>
        </div>

        <div class="login-heading">
            <h1>Welcome back</h1>
            <p>Sign in to manage your Tapsi Stock inventory.</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error" role="alert">
                <span class="login-error-icon">⚠</span>
                <span><?= h($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="login-form" novalidate>

            <div class="login-field">
                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="login-input"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="login-field">
                <label for="password">
                    Password
                </label>

                <div class="login-input-wrap">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="login-input password-input"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        aria-label="Show password"
                        title="Show password"
                    >
                        <svg
                            id="eyeIcon"
                            width="19"
                            height="19"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>

                </div>
            </div>

            <button type="submit" class="login-button">
                Sign In
            </button>

        </form>

        <div class="login-footer">
            Tapsi ni Melay &copy; <?= date('Y') ?>. All rights reserved.
        </div>

    </main>

</div>

<script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    passwordToggle.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';

        this.setAttribute(
            'aria-label',
            isPassword ? 'Hide password' : 'Show password'
        );

        this.setAttribute(
            'title',
            isPassword ? 'Hide password' : 'Show password'
        );
    });
</script>

</body>
</html>