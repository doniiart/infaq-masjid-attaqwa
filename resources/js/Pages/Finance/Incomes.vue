<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    incomes: Object,
    summary: Object,
});

const form = useForm({
    transaction_date: new Date().toISOString().slice(0, 10),
    amount: '',
    source_or_purpose: 'Tunai',
    donor_name: '',
    is_anonymous: false,
    description: '',
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

const submitIncome = () => {
    form.post(route('incomes.store'), {
        onSuccess: () => {
            form.reset('amount', 'donor_name', 'description');
            form.is_anonymous = false;
        },
    });
};
</script>

<template>
    <Head title="Pemasukan Infaq" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Manajemen Pemasukan Infaq</h2>
        </template>

        <div class="space-y-6">
            <!-- Ringkasan Singkat -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold uppercase text-emerald-600">Total Pemasukan Terverifikasi</p>
                    <p class="text-2xl font-bold text-[#008B45] mt-1">{{ formatRupiah(summary?.total_income) }}</p>
                </div>
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-5">
                    <p class="text-xs font-semibold uppercase text-[#008B45]">Saldo Dana Tersedia Saat Ini</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatRupiah(summary?.available_balance) }}</p>
                </div>
            </div>

            <!-- Form Catat Pemasukan -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">Catat Pemasukan Infaq Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-5">
                    Setiap pemasukan yang disimpan langsung berstatus <strong>Terverifikasi</strong> dan menambah saldo pembangunan.
                </p>

                <form @submit.prevent="submitIncome" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pemasukan *</label>
                        <input
                            v-model="form.transaction_date"
                            type="date"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.transaction_date" class="text-xs text-red-600 mt-1">{{ form.errors.transaction_date }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Pemasukan (Rp) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            min="1000"
                            step="100"
                            required
                            placeholder="Contoh: 500000"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.amount" class="text-xs text-red-600 mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber / Metode Pemasukan *</label>
                        <select
                            v-model="form.source_or_purpose"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        >
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Kotak Infaq Pembangunan">Kotak Infaq Pembangunan</option>
                            <option value="Metode Manual Lainnya">Metode Manual Lainnya</option>
                        </select>
                        <p v-if="form.errors.source_or_purpose" class="text-xs text-red-600 mt-1">{{ form.errors.source_or_purpose }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Donatur (Opsional)</label>
                        <input
                            v-model="form.donor_name"
                            type="text"
                            placeholder="Kosongkan jika anonim"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.donor_name" class="text-xs text-red-600 mt-1">{{ form.errors.donor_name }}</p>
                    </div>

                    <div class="md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Privasi Nama Donatur di Halaman Publik *</label>
                        <div class="flex flex-col sm:flex-row gap-4 text-sm">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" :value="false" v-model="form.is_anonymous" class="text-[#008B45] focus:ring-[#008B45]" />
                                <span>Tampilkan nama donatur (jika diisi)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" :value="true" v-model="form.is_anonymous" class="text-[#008B45] focus:ring-[#008B45]" />
                                <span>Sembunyikan nama donatur (tampil sebagai <strong>Hamba Allah</strong> di publik)</span>
                            </label>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Catatan tambahan pemasukan infaq..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        ></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pemasukan Infaq' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Pemasukan Terbaru -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Daftar Pemasukan Infaq</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Kode & Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold">Metode / Sumber</th>
                                <th class="px-4 py-3 text-left font-semibold">Nama Donatur (Internal)</th>
                                <th class="px-4 py-3 text-left font-semibold">Tampilan Publik</th>
                                <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!incomes?.data?.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada pemasukan infaq tercatat.</td>
                            </tr>
                            <tr v-for="tx in incomes?.data" :key="tx.id" :class="tx.status === 'cancelled' ? 'bg-red-50/40 text-slate-400' : ''">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="block font-mono text-xs text-slate-500">{{ tx.transaction_code }}</span>
                                    <span>{{ formatDateIndo(tx.transaction_date) }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-medium">{{ tx.source_or_purpose }}</span>
                                    <p v-if="tx.description" class="text-xs text-slate-500">{{ tx.description }}</p>
                                </td>
                                <td class="px-4 py-3.5">{{ tx.donor_name || '-' }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs">{{ tx.donor_public }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-[#008B45] whitespace-nowrap">
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
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="incomes?.links?.length > 3" class="px-6 py-4 border-t border-slate-100 flex justify-end gap-1">
                    <template v-for="(link, idx) in incomes.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="link.active ? 'bg-[#008B45] text-white' : 'bg-white text-slate-700'"
                            class="px-3 py-1 rounded border border-slate-200 text-xs"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>