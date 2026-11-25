<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col lg:flex-row">
        <!-- Panel Kiri: Branding & Informasi Masjid At-Taqwa (Tampil di Desktop) -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-[54%] bg-gradient-to-br from-[#005e2e] via-[#008B45] to-[#00a352] text-white p-12 flex-col justify-between relative overflow-hidden">
            <!-- Ornamen Geometris Halus -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-32 -left-24 w-[28rem] h-[28rem] rounded-full bg-black/10 pointer-events-none"></div>

            <!-- Header Branding -->
            <div class="relative z-10">
                <Link :href="route('home')" class="inline-flex items-center gap-3.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-white p-2 shadow-lg flex items-center justify-center transition-transform group-hover:scale-105">
                        <img
                            src="/images/logo-masjid-attaqwa.png"
                            alt="Logo Masjid At-Taqwa"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-widest text-emerald-200">
                            Sistem Informasi Infaq Pembangunan
                        </span>
                        <span class="block text-xl font-bold tracking-tight text-white">
                            Masjid At-Taqwa
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Konten Utama Panel Kiri -->
            <div class="relative z-10 max-w-lg space-y-6 my-auto py-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-xs font-medium text-emerald-50">
                    <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                    Portal Pengurus & Bendahara Pembangunan
                </div>

                <h1 class="text-3xl xl:text-4xl font-bold leading-tight">
                    Kelola Amanah Infaq Pembangunan Secara Transparan & Akuntabel.
                </h1>

                <p class="text-sm text-emerald-100 leading-relaxed">
                    Candi Rejo, RT 23 RW 09, Jelobo, Wonosari, Klaten, Jawa Tengah 57473.
                </p>

                <!-- 3 Pilar Sistem -->
                <div class="space-y-3.5 pt-2">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Pencatatan Real-Time & Anti-Saldo Negatif</h3>
                            <p class="text-xs text-emerald-100/90 mt-0.5">Setiap pemasukan dan pengeluaran langsung tervalidasi otomatis dengan bukti nota.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Jejak Audit & Privasi Donatur</h3>
                            <p class="text-xs text-emerald-100/90 mt-0.5">Identitas donatur anonim terlindungi di publik dan seluruh perubahan tercatat dalam log audit.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Laporan PDF & Dokumentasi Progres</h3>
                            <p class="text-xs text-emerald-100/90 mt-0.5">Cetak laporan keuangan berkala dan publikasikan progres fisik pembangunan ke jamaah.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Panel Kiri -->
            <div class="relative z-10 flex items-center justify-between text-xs text-emerald-200 border-t border-white/15 pt-5">
                <span>&copy; {{ new Date().getFullYear() }} Takmir & Panitia Pembangunan Masjid At-Taqwa</span>
                <Link :href="route('home')" class="font-semibold text-white hover:underline">
                    Lihat Beranda Publik &rarr;
                </Link>
            </div>
        </div>

        <!-- Panel Kanan: Form Autentikasi -->
        <div class="flex-1 flex flex-col justify-between p-6 sm:p-10 lg:p-12 bg-white lg:bg-slate-50">
            <!-- Header Mobile (Hanya tampil di layar kecil/HP) -->
            <div class="lg:hidden flex items-center justify-between pb-6 border-b border-slate-100">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <img
                        src="/images/logo-masjid-attaqwa.png"
                        alt="Logo Masjid At-Taqwa"
                        class="w-10 h-10 object-contain"
                    />
                    <div>
                        <span class="block text-sm font-bold text-slate-900">Masjid At-Taqwa</span>
                        <span class="block text-[11px] text-[#008B45] font-medium">Infaq Pembangunan</span>
                    </div>
                </Link>
                <Link
                    :href="route('home')"
                    class="text-xs font-semibold text-[#008B45] hover:text-[#007037] bg-emerald-50 px-3 py-1.5 rounded-lg"
                >
                    Beranda Publik
                </Link>
            </div>

            <!-- Kontainer Form Tengah -->
            <div class="w-full max-w-md mx-auto my-auto py-8">
                <div class="bg-white lg:p-8 lg:rounded-2xl lg:border lg:border-slate-200/80 lg:shadow-sm">
                    <slot />
                </div>
            </div>

            <!-- Footer Bawah -->
            <div class="text-center text-xs text-slate-400 pt-4">
                Sistem Informasi Pengelolaan Infaq Pembangunan Masjid At-Taqwa
            </div>
        </div>
    </div>
</template>