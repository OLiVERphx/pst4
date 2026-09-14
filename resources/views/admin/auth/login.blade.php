<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - Smartphone World</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <!-- Tailwind CDN (development) -->
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','system-ui'] } } } }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-[#0B0E17]">
    <div class="max-w-md w-full">
        <div class="bg-white dark:bg-[#131720] shadow-md rounded-lg p-6">
            <div class="text-center mb-4">
                <div class="text-3xl font-bold text-blue-600">SMARTPHONE WORLD C.A
                </div>
                <h1 class="mt-2 text-lg text-white font-semibold">Panel de administración</h1>
            </div>

            @if(session('error'))
                <div class="mb-3 text-sm text-red-600">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ url('/admin/login') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-white">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-gray-200 dark:border-gray-700 p-2 bg-gray-50 dark:bg-[#0f1116]" text-white required>
                    @error('email') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-white">Contraseña</label>
                    <input type="password" name="password" class="mt-1 w-full rounded border-gray-200 dark:border-gray-100 text-white p-2 bg-gray-50 dark:bg-[#0f1116]" required>
                    @error('password') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Iniciar sesión</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>