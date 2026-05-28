<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>@yield('title') &mdash; {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('library/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @stack('style')

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>
        body {
            background: var(--brand-gray-50);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .error-page {
            text-align: center;
            max-width: 540px;
            width: 100%;
        }
        .error-code {
            font-size: 96px;
            font-weight: 800;
            line-height: 1;
            color: var(--brand-primary);
            margin: 0 0 .5rem;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--brand-gray-900);
            margin-bottom: .5rem;
        }
        .error-description {
            color: var(--brand-gray-500);
            margin-bottom: 2rem;
            font-size: 15px;
            line-height: 1.6;
        }
        .error-logo {
            width: 48px; height: 48px;
            margin: 0 auto 1.5rem;
            display: block;
        }
        .error-actions {
            display: flex; gap: .75rem;
            justify-content: center; flex-wrap: wrap;
        }
        .error-actions .btn { padding: .6rem 1.5rem; border-radius: 8px; font-weight: 500; }
    </style>
</head>

<body>
    <div class="error-page">
        <img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" class="error-logo">
        @yield('main')
    </div>
</body>

</html>
