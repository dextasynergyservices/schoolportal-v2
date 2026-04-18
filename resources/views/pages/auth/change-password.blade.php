<x-layouts::auth :title="__('Change Password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Change Your Password')"
            :description="__('You must set a new password before continuing.')"
        />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.change.update') }}" class="flex flex-col gap-6">
            @csrf

            <x-password-input
                name="current_password"
                :label="__('Current Password')"
                required
                autocomplete="current-password"
                :placeholder="__('Enter your current password')"
                :with-strength-meter="false"
            />

            <x-password-input
                name="password"
                :label="__('New Password')"
                required
                :placeholder="__('Enter your new password')"
            />

            <x-password-input
                name="password_confirmation"
                :label="__('Confirm New Password')"
                required
                :placeholder="__('Confirm your new password')"
                :with-strength-meter="false"
            />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Change Password') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
