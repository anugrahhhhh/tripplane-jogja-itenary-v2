<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink">Profil Saya</h1>
        <p class="text-sm text-ink/60 mt-1">Kelola informasi akun dan keamanan akunmu.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white border border-primary-100 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-primary-100 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-primary-100 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.two-factor-security')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-red-100 rounded-2xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
