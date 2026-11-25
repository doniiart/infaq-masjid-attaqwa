<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    expenses: Object,
    summary: Object,
});

const fileInput = ref(null);

const form = useForm({
    transaction_date: new Date().toISOString().slice(0, 10),
    amount: '',
    source_or_purpose: '',
    description: '',
    attachment: null,
    is_public_attachment: false,
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

const handleFileChange = (event) => {
    form.attachment = event.target.files[0] || null;
};

const submitExpense = () => {
    form.post(route('expenses.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('amount', 'source_or_purpose', 'description', 'attachment');
            form.is_public_attachment = false;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};
</script>

<template>
    <Head title="Pengeluaran Pembangunan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Manajemen Pengeluaran Pembangunan</h2>
        </template>

        <div class="space-y-6">
            <!-- Saldo Aktif -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-5">
                    <p class="text-xs font-semibold uppercase text-[#008B45]">Saldo Dana Tersedia (Batas Maksimal Pengeluaran)</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ formatRupiah(summary?.available_balance) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs font-semibold uppercase text-amber-600">Total Pengeluaran Terverifikasi</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ formatRupiah(summary?.total_expense) }}</p>
                </div>
            </div>

            <!-- Form Catat Pengeluaran -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">Catat Pengeluaran Pembangunan</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-5">
                    Sistem otomatis menolak pengeluaran jika nominal melebihi saldo dana tersedia.
                </p>

                <form @submit.prevent="submitExpense" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pengeluaran *</label>
                        <input
                            v-model="form.transaction_date"
                            type="date"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.transaction_date" class="text-xs text-red-600 mt-1">{{ form.errors.transaction_date }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Pengeluaran (Rp) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            min="1000"
                            step="100"
                            required
                            placeholder="Contoh: 1250000"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.amount" class="text-xs text-red-600 mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keperluan Pengeluaran *</label>
                        <input
                            v-model="form.source_or_purpose"
                            type="text"
                            required
                            placeholder="Contoh: Pembelian 50 zak semen dan pasir pasang"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.source_or_purpose" class="text-xs text-red-600 mt-1">{{ form.errors.source_or_purpose }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Detail</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Nama toko/supplier, nomor nota, atau rincian pekerjaan..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bukti Nota / Kwitansi (JPG, PNG, WEBP, PDF - Maks 5MB)</label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            @change="handleFileChange"
                            class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-[#008B45] hover:file:bg-emerald-100"
                        />
                        <p v-if="form.errors.attachment" class="text-xs text-red-600 mt-1">{{ form.errors.attachment }}</p>
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_public_attachment"
                                class="rounded border-slate-300 text-[#008B45] focus:ring-[#008B45]"
                            />
                            <span>Tampilkan bukti nota ini di halaman publik</span>
                        </label>
                    </div>

                    <div class="md:col-span-2 flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengeluaran' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Pengeluaran -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Daftar Pengeluaran Pembangunan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Kode & Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold">Keperluan</th>
                                <th class="px-4 py-3 text-left font-semibold">Bukti</th>
                                <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!expenses?.data?.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada pengeluaran tercatat.</td>
                            </tr>
                            <tr v-for="tx in expenses?.data" :key="tx.id" :class="tx.status === 'cancelled' ? 'bg-red-50/40 text-slate-400' : ''">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="block font-mono text-xs text-slate-500">{{ tx.transaction_code }}</span>
                                    <span>{{ formatDateIndo(tx.transaction_date) }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-medium">{{ tx.source_or_purpose }}</span>
                                    <p v-if="tx.description" class="text-xs text-slate-500">{{ tx.description }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <template v-if="tx.attachments?.length">
                                        <a
                                            v-for="att in tx.attachments"
                                            :key="att.id"
                                            :href="att.url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-[#008B45] underline"
                                        >
                                            {{ att.file_name }} ({{ att.is_public ? 'Publik' : 'Privat' }})
                                        </a>
                                    </template>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-bold text-amber-600 whitespace-nowrap">
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

                <div v-if="expenses?.links?.length > 3" class="px-6 py-4 border-t border-slate-100 flex justify-end gap-1">
                    <template v-for="(link, idx) in expenses.links" :key="idx">
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