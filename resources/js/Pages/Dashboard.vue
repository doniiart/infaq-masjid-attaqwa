<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    summary: Object,
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
        </div>
    </AuthenticatedLayout>
</template>