<x-layouts.auth title="Log in" heading="Welcome back" subheading="Log in to your Xelenic account.">
    @if (session('status'))
        <x-alert class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <x-form.input name="email" type="email" label="Email" placeholder="you@company.com" autocomplete="email" required autofocus />
        <x-form.input name="password" type="password" label="Password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-4">
            <x-form.checkbox name="remember" label="Remember me" :checked="old('remember')" />
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Forgot password?</a>
        </div>

        <x-button class="w-full">Log in</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-700">Sign up</a>
    </p>
</x-layouts.auth>
