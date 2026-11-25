<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const mosque = computed(() => page.props.mosque);
const user = computed(() => page.props.auth?.user);
const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen flex flex-col bg-slate-50 text-slate-800">
        <!-- Navbar -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo & Identitas -->
                    <Link :href="route('home')" class="flex items-center gap-3">
                        <img
                            :src="mosque?.logo_path || '/images/logo-masjid-attaqwa.png'"
                            alt="Logo Masjid At-Taqwa"
                            class="h-12 w-auto object-contain"
                        />
                        <div>
                            <span class="block text-base sm:text-lg font-bold text-[#008B45] leading-tight">
                                {{ mosque?.name || 'Masjid At-Taqwa' }}
                            </span>
                            <span class="block text-xs text-slate-500">
                                Transparansi Infaq Pembangunan
                            </span>
                        </div>
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                        <a href="#ringkasan" class="text-slate-600 hover:text-[#008B45] transition">Ringkasan Dana</a>
                        <a href="#transaksi" class="text-slate-600 hover:text-[#008B45] transition">Riwayat Transaksi</a>
                        <a href="#progres" class="text-slate-600 hover:text-[#008B45] transition">Progres Pembangunan</a>
                        <a href="#kontak" class="text-slate-600 hover:text-[#008B45] transition">Kontak</a>

                        <Link
                            v-if="user"
                            :href="route('dashboard')"
                            class="px-4 py-2 rounded-lg bg-[#008B45] text-white font-semibold hover:bg-[#007037] transition"
                        >
                            Dashboard Pengurus
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="px-4 py-2 rounded-lg border border-[#008B45] text-[#008B45] font-semibold hover:bg-emerald-50 transition"
                        >
                            Login Pengurus
                        </Link>
                    </nav>

                    <!-- Mobile Toggle -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                        aria-label="Menu"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Nav -->
                <div v-if="mobileMenuOpen" class="md:hidden pb-4 border-t border-slate-100 space-y-2 pt-3 text-sm">
                    <a href="#ringkasan" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg hover:bg-emerald-50">Ringkasan Dana</a>
                    <a href="#transaksi" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg hover:bg-emerald-50">Riwayat Transaksi</a>
                    <a href="#progres" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg hover:bg-emerald-50">Progres Pembangunan</a>
                    <a href="#kontak" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg hover:bg-emerald-50">Kontak</a>
                    <Link
                        :href="user ? route('dashboard') : route('login')"
                        class="block text-center mt-2 px-4 py-2.5 rounded-lg bg-[#008B45] text-white font-semibold"
                    >
                        {{ user ? 'Dashboard Pengurus' : 'Login Pengurus' }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer id="kontak" class="bg-white border-t border-slate-200 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="flex items-start gap-4">
                    <img
                        :src="mosque?.logo_path || '/images/logo-masjid-attaqwa.png'"
                        alt="Logo Masjid At-Taqwa"
                        class="h-14 w-auto object-contain"
                    />
                    <div>
                        <h3 class="font-bold text-slate-900">{{ mosque?.name || 'Masjid At-Taqwa' }}</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            {{ mosque?.address || 'Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473' }}
                        </p>
                        <p class="text-sm text-[#008B45] font-medium mt-2">
                            Email: {{ mosque?.email || 'masjidattaqwa2309@gmail.com' }}
                        </p>
                    </div>
                </div>
                <div class="md:text-right flex flex-col justify-between text-sm text-slate-500">
                    <p>Sistem Informasi Pengelolaan Infaq Pembangunan Masjid At-Taqwa</p>
                    <p class="mt-2">&copy; {{ new Date().getFullYear() }} Takmir {{ mosque?.name || 'Masjid At-Taqwa' }}. Seluruh data keuangan tercatat secara transparan.</p>
                </div>
            </div>
        </footer>
    </div>
</template>