<x-layouts.auth title="Sign up" heading="Create your account" subheading="Get started with Xelenic in under a minute.">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf

        <x-form.input name="name" label="Name" placeholder="Your full name" autocomplete="name" required autofocus />
        <x-form.input name="email" type="email" label="Email" placeholder="you@company.com" autocomplete="email" required />
        <x-form.input name="password" type="password" label="Password" autocomplete="new-password" hint="At least 8 characters." required />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />

        <x-button class="w-full">Create account</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">Log in</a>
    </p>
</x-layouts.auth>
