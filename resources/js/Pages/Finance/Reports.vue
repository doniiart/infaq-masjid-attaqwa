<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    report: Object,
    filters: Object,
});

const form = reactive({
    period_type: props.filters?.period_type || 'monthly',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(amount || 0));
};

const previewReport = () => {
    router.get(route('reports.index'), form, {
        preserveState: true,
        preserveScroll: true,
    });
};

const pdfDownloadUrl = computed(() => {
    const params = new URLSearchParams({
        period_type: form.period_type,
    });
    if (form.period_type === 'custom') {
        if (form.start_date) params.append('start_date', form.start_date);
        if (form.end_date) params.append('end_date', form.end_date);
    }
    return route('reports.pdf') + '?' + params.toString();
});
</script>

<template>
    <Head title="Laporan Keuangan PDF" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Laporan Keuangan PDF</h2>
        </template>

        <div class="space-y-6">
            <!-- Filter Periode & Tombol Unduh PDF -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">Pilih Periode Laporan Keuangan</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-5">
                    Saldo awal periode dihitung otomatis dari posisi dana sebelum periode terpilih dimulai.
                </p>

                <form @submit.prevent="previewReport" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Periode</label>
                        <select
                            v-model="form.period_type"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        >
                            <option value="weekly">Mingguan (Minggu Ini)</option>
                            <option value="monthly">Bulanan (Bulan Ini)</option>
                            <option value="quarterly">Tiga Bulanan (Triwulan Ini)</option>
                            <option value="custom">Rentang Tanggal Khusus</option>
                        </select>
                    </div>

                    <div v-if="form.period_type === 'custom'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai *</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div v-if="form.period_type === 'custom'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Akhir *</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50"
                        >
                            Tampilkan Pratinjau
                        </button>
                        <a
                            :href="pdfDownloadUrl"
                            class="px-5 py-2.5 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037] inline-flex items-center gap-1.5"
                        >
                            Unduh PDF
                        </a>
                    </div>
                </form>
            </div>

            <!-- Pratinjau Ringkasan Laporan -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-6">
                <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ report.period.period_title }}</h3>
                        <p class="text-sm text-[#008B45] font-medium">{{ report.period.period_range_indo }}</p>
                    </div>
                    <span class="text-xs text-slate-500">Dibuat: {{ report.generated_at_indo }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs font-semibold uppercase text-slate-500">Saldo Awal Periode</p>
                        <p class="text-lg font-bold text-slate-900 mt-1">{{ formatRupiah(report.period_opening_balance) }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs font-semibold uppercase text-emerald-600">Total Pemasukan</p>
                        <p class="text-lg font-bold text-[#008B45] mt-1">+ {{ formatRupiah(report.total_income) }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs font-semibold uppercase text-amber-600">Total Pengeluaran</p>
                        <p class="text-lg font-bold text-amber-600 mt-1">- {{ formatRupiah(report.total_expense) }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                        <p class="text-xs font-semibold uppercase text-[#008B45]">Saldo Akhir Periode</p>
                        <p class="text-lg font-bold text-slate-900 mt-1">{{ formatRupiah(report.period_closing_balance) }}</p>
                    </div>
                </div>

                <!-- Tabel Pemasukan Periode -->
                <div>
                    <h4 class="font-bold text-sm text-slate-800 mb-2">Rincian Pemasukan ({{ report.incomes.length }} Transaksi)</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Tanggal</th>
                                    <th class="px-4 py-2.5 text-left">Kode</th>
                                    <th class="px-4 py-2.5 text-left">Sumber / Metode</th>
                                    <th class="px-4 py-2.5 text-left">Donatur (Publik)</th>
                                    <th class="px-4 py-2.5 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="!report.incomes.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada pemasukan pada periode ini.</td>
                                </tr>
                                <tr v-for="item in report.incomes" :key="item.id">
                                    <td class="px-4 py-2.5 whitespace-nowrap">{{ item.transaction_date_indo }}</td>
                                    <td class="px-4 py-2.5 font-mono text-slate-500">{{ item.transaction_code }}</td>
                                    <td class="px-4 py-2.5">{{ item.source_or_purpose }}</td>
                                    <td class="px-4 py-2.5">{{ item.donor_display }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-[#008B45]">{{ formatRupiah(item.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabel Pengeluaran Periode -->
                <div>
                    <h4 class="font-bold text-sm text-slate-800 mb-2">Rincian Pengeluaran ({{ report.expenses.length }} Transaksi)</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 text-slate-600 uppercase">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Tanggal</th>
                                    <th class="px-4 py-2.5 text-left">Kode</th>
                                    <th class="px-4 py-2.5 text-left">Keperluan</th>
                                    <th class="px-4 py-2.5 text-left">Keterangan</th>
                                    <th class="px-4 py-2.5 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="!report.expenses.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada pengeluaran pada periode ini.</td>
                                </tr>
                                <tr v-for="item in report.expenses" :key="item.id">
                                    <td class="px-4 py-2.5 whitespace-nowrap">{{ item.transaction_date_indo }}</td>
                                    <td class="px-4 py-2.5 font-mono text-slate-500">{{ item.transaction_code }}</td>
                                    <td class="px-4 py-2.5">{{ item.source_or_purpose }}</td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ item.description || '-' }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-amber-600">{{ formatRupiah(item.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>