<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Pengurus" />

        <!-- Judul Form -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                Masuk ke Sistem
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                Silakan masuk menggunakan akun pengurus yang telah didaftarkan oleh Ketua Takmir.
            </p>
        </div>

        <!-- Notifikasi Status -->
        <div v-if="status" class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-[#008B45]">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Input Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Alamat Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@masjidattaqwa.id"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#008B45] focus:ring-[#008B45] transition"
                    />
                </div>
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <!-- Input Password + Toggle Lihat/Sembunyikan -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-700">
                        Kata Sandi
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-[#008B45] hover:text-[#007037] hover:underline"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="w-full pl-10 pr-11 py-2.5 rounded-xl border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#008B45] focus:ring-[#008B45] transition"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    >
                        <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="remember"
                        v-model="form.remember"
                        class="rounded border-slate-300 text-[#008B45] focus:ring-[#008B45]"
                    />
                    <span class="text-xs text-slate-600">Ingat sesi masuk saya</span>
                </label>
            </div>

            <!-- Tombol Masuk -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-2.5 px-4 rounded-xl bg-[#008B45] text-white text-sm font-semibold shadow-sm hover:bg-[#007037] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#008B45] disabled:opacity-50 transition flex items-center justify-center gap-2"
            >
                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>{{ form.processing ? 'Memproses Masuk...' : 'Masuk ke Dashboard' }}</span>
            </button>
        </form>

        <!-- Tautan Kembali ke Publik -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <Link
                :href="route('home')"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-[#008B45] transition"
            >
                <span>&larr; Kembali ke Halaman Transparansi Publik</span>
            </Link>
        </div>
    </GuestLayout>
</template>