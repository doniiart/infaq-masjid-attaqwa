<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    openingBalances: Array,
    summary: Object,
});

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super_admin');
const editingId = ref(null);

const form = useForm({
    amount: '',
    recorded_date: new Date().toISOString().slice(0, 10),
    notes: '',
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

const submitForm = () => {
    if (editingId.value) {
        form.put(route('opening-balances.update', editingId.value), {
            onSuccess: () => {
                editingId.value = null;
                form.reset('amount', 'notes');
            },
        });
    } else {
        form.post(route('opening-balances.store'), {
            onSuccess: () => {
                form.reset('amount', 'notes');
            },
        });
    }
};

const startEdit = (item) => {
    editingId.value = item.id;
    form.amount = item.amount;
    form.recorded_date = item.recorded_date;
    form.notes = item.notes || '';
};

const cancelEdit = () => {
    editingId.value = null;
    form.reset('amount', 'notes');
    form.recorded_date = new Date().toISOString().slice(0, 10);
};
</script>

<template>
    <Head title="Saldo Awal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Pengelolaan Saldo Awal</h2>
        </template>

        <div class="space-y-6">
            <!-- Total Saldo Awal -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Saldo Awal Tercatat</p>
                    <p class="text-2xl sm:text-3xl font-bold text-[#008B45] mt-1">{{ formatRupiah(summary?.opening_balance) }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        Dana yang sudah tersedia sebelum pencatatan sistem dimulai. Menjadi dasar perhitungan saldo akhir.
                    </p>
                </div>
                <span v-if="!isSuperAdmin" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-medium">
                    Mode Lihat Saja (Khusus Super Admin)
                </span>
            </div>

            <!-- Form Input/Edit (Hanya Super Admin) -->
            <div v-if="isSuperAdmin" class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900 mb-4">
                    {{ editingId ? 'Ubah Catatan Saldo Awal' : 'Tambah Catatan Saldo Awal' }}
                </h3>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Saldo Awal (Rp) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            placeholder="Contoh: 25000000"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.amount" class="text-xs text-red-600 mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pencatatan *</label>
                        <input
                            v-model="form.recorded_date"
                            type="date"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.recorded_date" class="text-xs text-red-600 mt-1">{{ form.errors.recorded_date }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Catatan</label>
                        <input
                            v-model="form.notes"
                            type="text"
                            placeholder="Contoh: Saldo kas & rekening awal panitia"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.notes" class="text-xs text-red-600 mt-1">{{ form.errors.notes }}</p>
                    </div>

                    <div class="md:col-span-3 flex justify-end gap-2 pt-2">
                        <button
                            v-if="editingId"
                            type="button"
                            @click="cancelEdit"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : editingId ? 'Simpan Perubahan' : 'Simpan Saldo Awal' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Riwayat Saldo Awal -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Daftar Pencatatan Saldo Awal</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold">Tanggal Pencatatan</th>
                                <th class="px-6 py-3 text-left font-semibold">Keterangan</th>
                                <th class="px-6 py-3 text-left font-semibold">Dicatat Oleh</th>
                                <th class="px-6 py-3 text-right font-semibold">Nominal</th>
                                <th v-if="isSuperAdmin" class="px-6 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!openingBalances?.length">
                                <td :colspan="isSuperAdmin ? 5 : 4" class="px-6 py-8 text-center text-slate-500">
                                    Belum ada catatan saldo awal.
                                </td>
                            </tr>
                            <tr v-for="item in openingBalances" :key="item.id" class="hover:bg-slate-50">
                                <td class="px-6 py-4 whitespace-nowrap">{{ formatDateIndo(item.recorded_date) }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ item.notes || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-600">{{ item.creator_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-slate-900">{{ formatRupiah(item.amount) }}</td>
                                <td v-if="isSuperAdmin" class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="startEdit(item)"
                                        class="px-3 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:border-[#008B45] hover:text-[#008B45]"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>