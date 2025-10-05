<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Organization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Dark mode support
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center antialiased">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg dark:shadow-2xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="text-center mb-8">
            <div class="mb-4">
                <svg class="w-16 h-16 mx-auto text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Select Organization</h1>
            <p class="text-gray-600 dark:text-gray-400">
                Choose an organization to continue
            </p>
        </div>

        <form method="POST" action="{{ route('homework.select-organization') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $state }}">
            <input type="hidden" name="client_id" value="{{ $clientId }}">
            <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
            <input type="hidden" name="response_type" value="{{ $responseType }}">

            <div class="space-y-3 mb-6">
                @foreach($organizations as $org)
                    <button
                        type="submit"
                        name="organization_id"
                        value="{{ $org['id'] }}"
                        class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition duration-200 group"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition duration-200">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $org['name'] }}</span>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </button>
                @endforeach
            </div>

            @if($organizations->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No organizations available</p>
                </div>
            @endif
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Signed in as <span class="font-medium text-gray-900 dark:text-white">{{ $user->email }}</span>
            </p>
        </div>
    </div>
</body>
</html>
