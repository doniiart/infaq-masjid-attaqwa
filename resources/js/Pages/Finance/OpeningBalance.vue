<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    openingBalances: Array,
    totalOpeningBalance: Number,
});

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.user?.role === 'super_admin');
const editingId = ref(null);
const formCardRef = ref(null);

const form = useForm({
    amount: '',
    recorded_date: new Date().toISOString().slice(0, 10),
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
    const cleanDate = String(dateStr).slice(0, 10);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(cleanDate + 'T00:00:00'));
};

const startEdit = (item) => {
    editingId.value = item.id;
    form.clearErrors();
    form.amount = Number(item.amount);
    form.recorded_date = String(item.recorded_date).slice(0, 10);
    form.description = item.description || '';

    formCardRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const cancelEdit = () => {
    editingId.value = null;
    form.clearErrors();
    form.reset();
    form.recorded_date = new Date().toISOString().slice(0, 10);
};

const submitForm = () => {
    if (editingId.value) {
        form.put(route('opening-balances.update', editingId.value), {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('opening-balances.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('amount', 'description');
            },
        });
    }
};
</script>

<template>
    <Head title="Saldo Awal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Pengelolaan Saldo Awal</h2>
        </template>

        <div class="space-y-6">
            <!-- Kartu Total Saldo Awal -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Saldo Awal Tercatat</p>
                <p class="text-3xl font-bold text-[#008B45] mt-1">{{ formatRupiah(totalOpeningBalance) }}</p>
                <p class="text-xs text-slate-500 mt-1">
                    Dana yang sudah tersedia sebelum pencatatan sistem dimulai. Menjadi dasar perhitungan saldo akhir.
                </p>
            </div>

            <!-- Form Tambah / Edit (Khusus Ketua Takmir) -->
            <div
                v-if="isSuperAdmin"
                ref="formCardRef"
                :class="editingId ? 'border-[#008B45] ring-2 ring-emerald-500/20 bg-emerald-50/20' : 'border-slate-200 bg-white'"
                class="rounded-xl border p-6 transition-all"
            >
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h3 class="font-bold text-slate-900">
                        {{ editingId ? 'Ubah Catatan Saldo Awal' : 'Tambah Catatan Saldo Awal' }}
                    </h3>
                    <span
                        v-if="editingId"
                        class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-[#008B45] text-xs font-semibold"
                    >
                        Mode Edit Aktif
                    </span>
                </div>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Saldo Awal (Rp) *</label>
                        <input
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="1"
                            required
                            placeholder="Contoh: 15000000"
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
                            v-model="form.description"
                            type="text"
                            placeholder="Contoh: Saldo kas panitia lama"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
                    </div>

                    <div class="md:col-span-3 flex justify-end gap-2 pt-2">
                        <button
                            v-if="editingId"
                            type="button"
                            @click="cancelEdit"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan' : 'Simpan Saldo Awal') }}
                        </button>
                    </div>
                </form>
            </div>

            <div v-else class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800">
                Hanya Ketua Takmir yang dapat menambah atau memperbarui saldo awal.
            </div>

            <!-- Tabel Daftar Saldo Awal -->
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
                            <tr
                                v-for="item in openingBalances"
                                :key="item.id"
                                :class="editingId === item.id ? 'bg-emerald-50/60' : 'hover:bg-slate-50'"
                            >
                                <td class="px-6 py-3.5 whitespace-nowrap">{{ formatDateIndo(item.recorded_date) }}</td>
                                <td class="px-6 py-3.5 text-slate-700">{{ item.description || '-' }}</td>
                                <td class="px-6 py-3.5 text-slate-600">{{ item.creator_name }}</td>
                                <td class="px-6 py-3.5 text-right font-bold text-[#008B45]">{{ formatRupiah(item.amount) }}</td>
                                <td v-if="isSuperAdmin" class="px-6 py-3.5 text-center">
                                    <button
                                        type="button"
                                        @click="startEdit(item)"
                                        :class="editingId === item.id ? 'border-[#008B45] bg-[#008B45] text-white' : 'border-slate-300 text-slate-700 hover:border-[#008B45] hover:text-[#008B45]'"
                                        class="px-3 py-1 rounded-lg border text-xs font-semibold transition"
                                    >
                                        {{ editingId === item.id ? 'Sedang Diedit' : 'Edit' }}
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