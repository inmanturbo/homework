<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorization Required - WorkOS OAuth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Authorization Required</h1>
            <p class="text-gray-600 mt-2">{{ $client->name }} is requesting permission to access your account</p>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-3">This application will be able to:</h3>
            <ul class="list-disc list-inside text-gray-700 space-y-1">
                @forelse($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @empty
                    <li>Access your basic profile information</li>
                @endforelse
            </ul>
        </div>

        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <div class="flex space-x-4">
                <button type="submit" name="approve" value="1"
                        class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    Authorize
                </button>

                <button type="submit" name="approve" value="0"
                        class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200">
                    Cancel
                </button>
            </div>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            Signed in as {{ $user->email }}
        </div>
    </div>
</body>
</html>