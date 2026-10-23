<x-layouts.auth title="Reset password" heading="Choose a new password" subheading="Pick something strong that you don't use anywhere else.">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.input name="email" type="email" label="Email" :value="$request->email" autocomplete="email" required />
        <x-form.input name="password" type="password" label="New password" autocomplete="new-password" required autofocus />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />

        <x-button class="w-full">Reset password</x-button>
    </form>
</x-layouts.auth>
