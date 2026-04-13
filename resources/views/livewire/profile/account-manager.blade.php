<div class="min-h-screen bg-slate-100 py-8">
    <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-semibold text-slate-900">Mi cuenta</h1>
            <p class="mt-2 text-sm text-slate-600">Gestiona tus datos de perfil, seguridad y preferencias.</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <livewire:profile.avatar-upload />
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <livewire:profile.update-profile-information />
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <livewire:profile.update-password />
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <livewire:profile.preferences />
        </div>

        <div class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
            <livewire:profile.delete-account />
        </div>
    </div>
</div>
