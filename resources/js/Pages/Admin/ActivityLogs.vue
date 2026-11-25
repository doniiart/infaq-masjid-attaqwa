<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    action: props.filters?.action || '',
    date: props.filters?.date || '',
});

const selectedLog = ref(null);

const applyFilters = () => {
    router.get(route('activity-logs.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.action = '';
    filterForm.date = '';
    applyFilters();
};
</script>

<template>
    <Head title="Log Aktivitas Audit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Log Aktivitas Audit Sistem</h2>
        </template>

        <div class="space-y-6">
            <form @submit.prevent="applyFilters" class="bg-white rounded-xl border border-slate-200 p-5 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Aktivitas / Nama Pengguna</label>
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Cari deskripsi aktivitas atau nama pengurus..."
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                    />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori Aksi</label>
                    <select v-model="filterForm.action" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]">
                        <option value="">Semua Aktivitas</option>
                        <option value="auth.">Login & Logout</option>
                        <option value="opening_balance.">Saldo Awal</option>
                        <option value="transaction.">Transaksi & Pembatalan</option>
                        <option value="construction.">Dokumentasi Pembangunan</option>
                        <option value="user.">Manajemen Pengguna</option>
                        <option value="backup.">Backup Database</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal</label>
                    <input v-model="filterForm.date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]" />
                </div>
                <div class="sm:col-span-4 flex justify-end gap-2">
                    <button type="button" @click="resetFilters" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">
                        Reset
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037]">
                        Filter Log
                    </button>
                </div>
            </form>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold">Pengguna</th>
                                <th class="px-4 py-3 text-left font-semibold">Kode Aksi</th>
                                <th class="px-4 py-3 text-left font-semibold">Deskripsi Aktivitas</th>
                                <th class="px-4 py-3 text-center font-semibold">Snapshot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!logs?.data?.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada log aktivitas tercatat.</td>
                            </tr>
                            <tr v-for="log in logs?.data" :key="log.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ log.created_at }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="block font-medium text-slate-900">{{ log.user_name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ log.ip_address }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-xs text-slate-700">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ log.description }}</td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button
                                        v-if="log.old_values || log.new_values"
                                        type="button"
                                        @click="selectedLog = log"
                                        class="px-2.5 py-1 rounded border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Data
                                    </button>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs?.links?.length > 3" class="px-6 py-4 border-t border-slate-100 flex justify-end gap-1">
                    <template v-for="(link, idx) in logs.links" :key="idx">
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

        <!-- Modal Snapshot Data Audit -->
        <div v-if="selectedLog" @click.self="selectedLog = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900">Detail Snapshot Audit ({{ selectedLog.action }})</h3>
                    <button @click="selectedLog = null" class="text-slate-400 hover:text-slate-700 text-xl">&times;</button>
                </div>

                <div v-if="selectedLog.old_values">
                    <p class="text-xs font-bold uppercase text-red-600 mb-1">Data Sebelumnya (Old Values)</p>
                    <pre class="bg-slate-900 text-slate-100 p-3 rounded-lg text-xs overflow-x-auto">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                </div>

                <div v-if="selectedLog.new_values">
                    <p class="text-xs font-bold uppercase text-emerald-600 mb-1">Data Baru (New Values)</p>
                    <pre class="bg-slate-900 text-slate-100 p-3 rounded-lg text-xs overflow-x-auto">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>