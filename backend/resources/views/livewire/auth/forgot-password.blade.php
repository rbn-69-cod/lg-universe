<x-layouts.auth>
    <div class="relative min-h-screen overflow-hidden bg-[#080a10] text-white">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#080a10_0%,#111827_48%,#101316_100%)]"></div>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
            <div class="w-full max-w-md rounded-lg border border-white/10 bg-[#0d1117]/92 p-7 shadow-[0_28px_90px_rgba(0,0,0,.45)] backdrop-blur-xl sm:p-9">
                <a href="{{ route('login') }}" class="mb-8 inline-flex items-center gap-3 text-white">
                    <span class="grid h-11 w-11 place-items-center rounded-lg border border-white/15 bg-white/10 font-black">LG</span>
                    <span class="font-black">LG Universe</span>
                </a>

                <h1 class="text-3xl font-black tracking-tight">Recuperar acceso</h1>
                <p class="mt-3 text-sm leading-6 text-white/60">
                    Ingresa tu correo y Laravel enviara un enlace seguro para crear una nueva contrasena.
                </p>

                <x-auth-session-status class="mt-5 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-3 text-sm text-emerald-200" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
                    @csrf

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-white/78">Correo</span>
                        <input
                            name="email"
                            type="email"
                            value="{{ old('email', 'igr.ruben@gmail.com') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-white/10 bg-white/[.08] px-4 py-4 text-white outline-none transition placeholder:text-white/32 focus:border-cyan-300/60 focus:ring-4 focus:ring-cyan-300/10"
                            placeholder="igr.ruben@gmail.com"
                        >
                        @error('email')
                            <span class="mt-2 block text-sm text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <button class="w-full rounded-lg bg-[#35f0b0] px-5 py-4 font-black text-[#061014] transition hover:-translate-y-0.5 hover:bg-[#6ff7c9]">
                        Enviar enlace de recuperacion
                    </button>
                </form>

                <div class="mt-6 text-center text-sm">
                    <a href="{{ route('login') }}" class="font-bold text-cyan-200 hover:text-white">
                        Volver al login
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.auth>
