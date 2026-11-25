<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const mosque = computed(() => page.props.mosque);
</script>

<template>
    <Head title="Beranda - Transparansi Infaq Pembangunan" />

    <PublicLayout>
        <section class="bg-gradient-to-br from-[#008B45] to-emerald-800 text-white py-14 sm:py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <span class="inline-block px-3 py-1 rounded-full bg-white/15 text-emerald-100 text-xs font-semibold tracking-wide uppercase mb-4">
                        Laporan Keuangan & Progres Pembangunan
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-bold leading-tight">
                        Sistem Informasi Pengelolaan Infaq Pembangunan {{ mosque?.name || 'Masjid At-Taqwa' }}
                    </h1>
                    <p class="mt-4 text-emerald-100 text-base sm:text-lg leading-relaxed">
                        {{ mosque?.about_description }}
                    </p>
                    <p class="mt-3 text-sm text-emerald-200">
                        Lokasi: {{ mosque?.address }}
                    </p>
                </div>
            </div>
        </section>

        <section id="ringkasan" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-sm font-medium text-slate-500">Total Pemasukan Infaq</p>
                    <p class="text-2xl font-bold text-[#008B45] mt-2">Rp 0</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-sm font-medium text-slate-500">Total Pengeluaran Pembangunan</p>
                    <p class="text-2xl font-bold text-amber-600 mt-2">Rp 0</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-sm font-medium text-slate-500">Saldo Dana Tersedia</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">Rp 0</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>