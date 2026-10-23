<x-layouts.auth title="Forgot password" heading="Forgot your password?" subheading="Enter your email and we'll send you a link to choose a new one.">
    @if (session('status'))
        <x-alert class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-form.input name="email" type="email" label="Email" placeholder="you@company.com" autocomplete="email" required autofocus />

        <x-button class="w-full">Email reset link</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Remembered it?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">Back to log in</a>
    </p>
</x-layouts.auth>
