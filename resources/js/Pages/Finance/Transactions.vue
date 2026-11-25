<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    transactions: Object,
    summary: Object,
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const selectedTx = ref(null);
const cancellingTx = ref(null);

const cancelForm = useForm({
    cancellation_reason: '',
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(amount || 0));
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(dateStr + 'T00:00:00'));
};

const applyFilters = () => {
    router.get(route('transactions.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.type = '';
    filterForm.status = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    applyFilters();
};

const openCancelModal = (tx) => {
    cancellingTx.value = tx;
    cancelForm.reset();
    cancelForm.clearErrors();
};

const submitCancel = () => {
    if (!cancellingTx.value) return;
    cancelForm.post(route('transactions.cancel', cancellingTx.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancellingTx.value = null;
            cancelForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Riwayat Transaksi & Pembatalan</h2>
        </template>

        <div class="space-y-6">
            <!-- Ringkasan Saldo -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase text-emerald-600">Total Pemasukan Aktif</p>
                    <p class="text-xl font-bold text-[#008B45] mt-1">{{ formatRupiah(summary?.total_income) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-xs font-semibold uppercase text-amber-600">Total Pengeluaran Aktif</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ formatRupiah(summary?.total_expense) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4">
                    <p class="text-xs font-semibold uppercase text-[#008B45]">Saldo Aktif Tersedia</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ formatRupiah(summary?.available_balance) }}</p>
                </div>
            </div>

            <!-- Filter Pencarian -->
            <form @submit.prevent="applyFilters" class="bg-white rounded-xl border border-slate-200 p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pencarian Internal</label>
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Kode, donatur asli, sumber/keperluan..."
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis</label>
                    <select v-model="filterForm.type" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]">
                        <option value="">Semua Jenis</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]">
                        <option value="">Semua Status</option>
                        <option value="verified">Terverifikasi</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                    <input v-model="filterForm.date_from" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                    <input v-model="filterForm.date_to" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]" />
                </div>
                <div class="sm:col-span-2 lg:col-span-6 flex justify-end gap-2 pt-1">
                    <button type="button" @click="resetFilters" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037]">
                        Filter Transaksi
                    </button>
                </div>
            </form>

            <!-- Tabel Riwayat Transaksi -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Kode & Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold">Sumber / Keperluan</th>
                                <th class="px-4 py-3 text-left font-semibold">Donatur (Internal / Publik)</th>
                                <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!transactions?.data?.length">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">Tidak ada transaksi yang ditemukan.</td>
                            </tr>
                            <tr
                                v-for="tx in transactions?.data"
                                :key="tx.id"
                                :class="tx.status === 'cancelled' ? 'bg-red-50/40 text-slate-500' : 'hover:bg-slate-50'"
                            >
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="block font-mono text-xs text-slate-500">{{ tx.transaction_code }}</span>
                                    <span>{{ formatDateIndo(tx.transaction_date) }}</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span
                                        :class="tx.type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ tx.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-medium text-slate-800">{{ tx.source_or_purpose }}</span>
                                    <p v-if="tx.description" class="text-xs text-slate-500 line-clamp-1">{{ tx.description }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <template v-if="tx.type === 'income'">
                                        <span class="block font-medium text-slate-800">{{ tx.donor_name || 'Anonim' }}</span>
                                        <span class="text-slate-500">Publik: {{ tx.donor_public }}</span>
                                    </template>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td
                                    :class="tx.status === 'cancelled' ? 'line-through text-slate-400' : tx.type === 'income' ? 'text-[#008B45]' : 'text-amber-600'"
                                    class="px-4 py-3.5 text-right font-bold whitespace-nowrap"
                                >
                                    {{ formatRupiah(tx.amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        :class="tx.status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ tx.status === 'verified' ? 'Terverifikasi' : 'Dibatalkan' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap space-x-1.5">
                                    <button
                                        type="button"
                                        @click="selectedTx = tx"
                                        class="px-2.5 py-1 rounded border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Detail
                                    </button>
                                    <button
                                        v-if="tx.status === 'verified'"
                                        type="button"
                                        @click="openCancelModal(tx)"
                                        class="px-2.5 py-1 rounded border border-red-200 bg-red-50 text-xs font-semibold text-red-600 hover:bg-red-100"
                                    >
                                        Batalkan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions?.links?.length > 3" class="px-6 py-4 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-xs text-slate-500">Total {{ transactions.total }} transaksi</span>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in transactions.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                                :class="link.active ? 'bg-[#008B45] text-white' : 'bg-white text-slate-700'"
                                class="px-3 py-1 rounded border border-slate-200 text-xs"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Detail Transaksi Internal -->
        <div v-if="selectedTx" @click.self="selectedTx = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                    <div>
                        <span class="font-mono text-xs text-slate-400">{{ selectedTx.transaction_code }}</span>
                        <h3 class="text-lg font-bold text-slate-900">Detail Audit Transaksi</h3>
                    </div>
                    <button @click="selectedTx = null" class="text-slate-400 hover:text-slate-700 text-xl">&times;</button>
                </div>

                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal</span>
                        <span class="font-medium">{{ formatDateIndo(selectedTx.transaction_date) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Jenis</span>
                        <span class="font-semibold">{{ selectedTx.type === 'income' ? 'Pemasukan Infaq' : 'Pengeluaran Pembangunan' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nominal</span>
                        <span class="font-bold text-base text-[#008B45]">{{ formatRupiah(selectedTx.amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Dicatat Oleh</span>
                        <span class="font-medium">{{ selectedTx.creator_name }}</span>
                    </div>
                    <div v-if="selectedTx.type === 'income'" class="flex justify-between">
                        <span class="text-slate-500">Nama Asli Donatur</span>
                        <span class="font-medium">{{ selectedTx.donor_name || '-' }} (Publik: {{ selectedTx.donor_public }})</span>
                    </div>
                    <div>
                        <span class="block text-slate-500 mb-1">Keterangan</span>
                        <p class="bg-slate-50 p-3 rounded-lg text-slate-800">{{ selectedTx.description || '-' }}</p>
                    </div>
                    <div v-if="selectedTx.attachments?.length">
                        <span class="block text-slate-500 mb-1">Lampiran Bukti</span>
                        <a
                            v-for="att in selectedTx.attachments"
                            :key="att.id"
                            :href="att.url"
                            target="_blank"
                            class="block text-xs text-[#008B45] underline"
                        >
                            {{ att.file_name }} ({{ att.is_public ? 'Publik' : 'Privat' }})
                        </a>
                    </div>

                    <!-- Info Pembatalan jika dibatalkan -->
                    <div v-if="selectedTx.status === 'cancelled'" class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-900 space-y-1 text-xs">
                        <p class="font-bold uppercase">Transaksi Dibatalkan</p>
                        <p><strong>Dibatalkan oleh:</strong> {{ selectedTx.canceller_name || '-' }} ({{ selectedTx.cancelled_at }})</p>
                        <p><strong>Alasan:</strong> {{ selectedTx.cancellation_reason }}</p>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button @click="selectedTx = null" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Pembatalan Transaksi -->
        <div v-if="cancellingTx" @click.self="cancellingTx = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
                <h3 class="text-lg font-bold text-red-700">Batalkan Transaksi {{ cancellingTx.transaction_code }}?</h3>
                <p class="text-xs text-slate-600 mt-1">
                    Transaksi tidak akan dihapus permanen, melainkan ditandai <strong>Dibatalkan</strong>, dikeluarkan dari perhitungan saldo aktif, dan dicatat dalam audit log.
                </p>

                <div class="mt-4 p-3 rounded-lg bg-slate-50 text-xs space-y-1">
                    <p><strong>Nominal:</strong> {{ formatRupiah(cancellingTx.amount) }}</p>
                    <p><strong>Sumber/Keperluan:</strong> {{ cancellingTx.source_or_purpose }}</p>
                </div>

                <form @submit.prevent="submitCancel" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pembatalan Wajib Diisi *</label>
                        <textarea
                            v-model="cancelForm.cancellation_reason"
                            rows="3"
                            required
                            placeholder="Jelaskan alasan pembatalan transaksi secara jelas..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                        ></textarea>
                        <p v-if="cancelForm.errors.cancellation_reason" class="text-xs text-red-600 mt-1">
                            {{ cancelForm.errors.cancellation_reason }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="cancellingTx = null"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ cancelForm.processing ? 'Memproses...' : 'Konfirmasi Batalkan Transaksi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>