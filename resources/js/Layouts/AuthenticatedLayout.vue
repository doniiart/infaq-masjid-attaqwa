<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const mosque = computed(() => page.props.mosque);
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');
const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex">
        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
        />

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed lg:static inset-y-0 left-0 z-40 w-68 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200"
        >
            <div class="h-20 px-5 flex items-center gap-3 border-b border-slate-200">
                <img
                    :src="mosque?.logo_path || '/images/logo-masjid-attaqwa.png'"
                    alt="Logo Masjid At-Taqwa"
                    class="h-11 w-auto object-contain"
                />
                <div class="min-w-0">
                    <p class="font-bold text-sm text-[#008B45] truncate">{{ mosque?.name || 'Masjid At-Taqwa' }}</p>
                    <span class="inline-block text-[11px] font-semibold px-2 py-0.5 rounded bg-emerald-50 text-[#008B45] uppercase">
                        {{ isSuperAdmin ? 'Super Admin' : 'Admin Takmir' }}
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-sm font-medium">
                <Link
                    :href="route('dashboard')"
                    :class="route().current('dashboard') ? 'bg-[#008B45] text-white' : 'text-slate-700 hover:bg-emerald-50 hover:text-[#008B45]'"
                    class="flex items-center px-3 py-2.5 rounded-lg transition"
                >
                    Dashboard Ringkasan
                </Link>

                <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Keuangan & Laporan
                </div>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Saldo Awal (Fase 4)</span>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Pemasukan Infaq (Fase 4)</span>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Pengeluaran (Fase 4)</span>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Riwayat Transaksi (Fase 4)</span>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Laporan PDF (Fase 5)</span>

                <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Pembangunan
                </div>
                <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Dokumentasi & Progres (Fase 6)</span>

                <template v-if="isSuperAdmin">
                    <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Administrasi Sistem
                    </div>
                    <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Manajemen Pengguna (Fase 7)</span>
                    <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Profil Masjid (Fase 7)</span>
                    <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Log Aktivitas (Fase 4)</span>
                    <span class="block px-3 py-2 text-slate-400 cursor-not-allowed">Backup Database (Fase 8)</span>
                </template>
            </nav>

            <div class="p-4 border-t border-slate-200 space-y-2 text-sm">
                <Link :href="route('home')" class="block text-center py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                    Lihat Halaman Publik
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full py-2 rounded-lg bg-red-50 text-red-600 font-semibold hover:bg-red-100 transition"
                >
                    Keluar (Logout)
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <slot name="header" />
                </div>

                <Link :href="route('profile.edit')" class="text-sm font-medium text-slate-700 hover:text-[#008B45]">
                    {{ user.name }}
                </Link>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div v-if="$page.props.flash?.success" class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                    {{ $page.props.flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>