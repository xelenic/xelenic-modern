{{-- Both forms post to Fortify, which redirects back here with a status code. --}}
<x-layouts.app title="Profile">
    <div class="max-w-3xl space-y-6">
        <x-card title="Profile information" description="Update your name and email address.">
            @if (session('status') === 'profile-information-updated')
                <x-alert class="mb-6">Your profile was saved.</x-alert>
            @endif

            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form.input name="name" label="Name" :value="auth()->user()->name" bag="updateProfileInformation" autocomplete="name" required />
                <x-form.input name="email" type="email" label="Email" :value="auth()->user()->email" bag="updateProfileInformation" autocomplete="email" required />

                <div class="flex justify-end">
                    <x-button>Save</x-button>
                </div>
            </form>
        </x-card>

        <x-card id="password" title="Update password" description="Use a long, random password to keep your account secure.">
            @if (session('status') === 'password-updated')
                <x-alert class="mb-6">Your password was changed.</x-alert>
            @endif

            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form.input name="current_password" type="password" label="Current password" bag="updatePassword" autocomplete="current-password" required />
                <x-form.input name="password" type="password" label="New password" bag="updatePassword" autocomplete="new-password" required />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" bag="updatePassword" autocomplete="new-password" required />

                <div class="flex justify-end">
                    <x-button>Update password</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
