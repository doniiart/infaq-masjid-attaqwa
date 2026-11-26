<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: Object,
    monthlyIncomeChart: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(amount || 0));
};

const chartData = computed(() => props.monthlyIncomeChart || []);

const maxIncome = computed(() => {
    const max = Math.max(...chartData.value.map((item) => Number(item.total || 0)), 0);
    return max > 0 ? max : 1;
});

const totalSixMonthsIncome = computed(() => {
    return chartData.value.reduce((sum, item) => sum + Number(item.total || 0), 0);
});

const averageMonthlyIncome = computed(() => {
    if (!chartData.value.length) return 0;
    return Math.round(totalSixMonthsIncome.value / chartData.value.length);
});

const chartPeriodLabel = computed(() => {
    if (chartData.value.length < 2) return '6 Bulan Terakhir';
    return `${chartData.value[0].month} - ${chartData.value[chartData.value.length - 1].month}`;
});

const getBarHeight = (amount) => {
    const val = Number(amount || 0);
    if (val <= 0) return '6%';
    const percentage = Math.round((val / maxIncome.value) * 100);
    return `${Math.max(percentage, 12)}%`;
};

const formatCompactRupiah = (amount) => {
    const val = Number(amount || 0);
    if (val === 0) return 'Rp 0';
    if (val >= 1_000_000_000) return `Rp ${(val / 1_000_000_000).toFixed(1).replace('.', ',')} M`;
    if (val >= 1_000_000) return `Rp ${(val / 1_000_000).toFixed(1).replace('.', ',')} jt`;
    if (val >= 1_000) return `Rp ${Math.round(val / 1_000)} rb`;
    return `Rp ${val.toLocaleString('id-ID')}`;
};
</script>

<template>
    <Head title="Dashboard Pengurus" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Dashboard Pengurus</h2>
        </template>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900">Assalamu'alaikum, {{ user.name }}</h3>
                <p class="text-sm text-slate-600 mt-1">
                    Anda login sebagai <strong class="uppercase text-[#008B45]">{{ user.role === 'super_admin' ? 'Ketua Takmir' : 'Jamaah' }}</strong>.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold uppercase text-slate-400">Saldo Awal</p>
                    <p class="text-xl font-bold text-slate-900 mt-2">{{ formatRupiah(summary?.opening_balance) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold uppercase text-emerald-600">Total Pemasukan</p>
                    <p class="text-xl font-bold text-[#008B45] mt-2">{{ formatRupiah(summary?.total_income) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold uppercase text-amber-600">Total Pengeluaran</p>
                    <p class="text-xl font-bold text-amber-600 mt-2">{{ formatRupiah(summary?.total_expense) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-5">
                    <p class="text-xs font-semibold uppercase text-[#008B45]">Saldo Tersedia</p>
                    <p class="text-xl font-bold text-slate-900 mt-2">{{ formatRupiah(summary?.available_balance) }}</p>
                </div>
            </div>

            <!-- Grafik Pemasukan Infaq 6 Bulan Terakhir -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-5 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#008B45]"></span>
                            <h3 class="font-bold text-base text-slate-900">
                                Grafik Pemasukan Infaq (6 Bulan Terakhir)
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Akumulasi pemasukan infaq terverifikasi periode <span class="font-semibold text-slate-700">{{ chartPeriodLabel }}</span>.
                        </p>
                    </div>

                    <!-- Ringkasan Angka & Tombol Cepat -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="px-3.5 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Total 6 Bulan</span>
                            <span class="text-sm font-bold text-[#008B45]">
                                {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(totalSixMonthsIncome) }}
                            </span>
                        </div>

                        <div class="px-3.5 py-2 rounded-lg bg-slate-50 border border-slate-200/80">
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500">Rata-rata / Bulan</span>
                            <span class="text-sm font-bold text-slate-800">
                                {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(averageMonthlyIncome) }}
                            </span>
                        </div>

                        <Link
                            :href="route('incomes.index')"
                            class="px-3.5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037] transition"
                        >
                            + Catat Pemasukan
                        </Link>
                    </div>
                </div>

                <!-- Area Batang Grafik -->
                <div class="mt-6">
                    <div class="relative h-64 pt-8 pb-2 px-2 sm:px-6">
                        <!-- Garis Bantu Horizontal -->
                        <div class="absolute inset-x-2 sm:inset-x-6 top-8 bottom-2 flex flex-col justify-between pointer-events-none">
                            <div class="border-b border-dashed border-slate-200 w-full"></div>
                            <div class="border-b border-dashed border-slate-200 w-full"></div>
                            <div class="border-b border-dashed border-slate-200 w-full"></div>
                            <div class="border-b border-slate-200 w-full"></div>
                        </div>

                        <!-- 6 Kolom Bulan -->
                        <div class="relative z-10 h-full grid grid-cols-6 gap-2 sm:gap-5 items-end">
                            <div
                                v-for="(item, index) in chartData"
                                :key="index"
                                class="h-full flex flex-col items-center justify-end group"
                            >
                                <!-- Label Nominal Ringkas di Atas Batang -->
                                <span
                                    :class="item.total > 0 ? 'text-[#008B45] font-bold' : 'text-slate-400 font-medium'"
                                    class="text-[11px] sm:text-xs mb-1.5 text-center whitespace-nowrap"
                                >
                                    {{ formatCompactRupiah(item.total) }}
                                </span>

                                <!-- Batang Grafik + Tooltip Hover -->
                                <div class="w-full max-w-[54px] h-[78%] flex items-end justify-center relative">
                                    <!-- Tooltip Detail saat Hover -->
                                    <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity absolute -top-14 left-1/2 -translate-x-1/2 z-20 bg-slate-900 text-white text-[11px] rounded-lg px-2.5 py-1.5 shadow-lg whitespace-nowrap text-center">
                                        <p class="font-semibold">{{ item.month }}</p>
                                        <p class="text-emerald-300 font-bold">
                                            {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.total) }}
                                        </p>
                                        <p class="text-[10px] text-slate-300">{{ item.count }} transaksi</p>
                                    </div>

                                    <div
                                        :style="{ height: getBarHeight(item.total) }"
                                        :class="item.total > 0
                                            ? 'bg-gradient-to-t from-[#008B45] to-emerald-400 group-hover:from-[#007037] group-hover:to-emerald-300 shadow-sm'
                                            : 'bg-slate-200/80'"
                                        class="w-full rounded-t-lg transition-all duration-500 ease-out cursor-pointer"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Label Nama Bulan di Bawah Garis Dasar -->
                    <div class="grid grid-cols-6 gap-2 sm:gap-5 px-2 sm:px-6 pt-2.5">
                        <div
                            v-for="(item, index) in chartData"
                            :key="'label-' + index"
                            class="text-center"
                        >
                            <span class="block text-xs font-semibold text-slate-700">{{ item.short_month }}</span>
                            <span class="hidden sm:block text-[10px] text-slate-400">{{ item.month.split(' ')[1] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>