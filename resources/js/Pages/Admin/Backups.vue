<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    backups: Object,
});

const restoringItem = ref(null);
const uploadFileInput = ref(null);

const createForm = useForm({});

const restoreLogForm = useForm({
    confirmation: '',
});

const restoreUploadForm = useForm({
    backup_file: null,
    confirmation: '',
});

const typeBadge = (item) => {
    if (item.file_name?.includes('pre_restore')) return { text: 'Pengaman Pre-Restore', class: 'bg-purple-100 text-purple-800' };
    if (item.type !== 'manual') return { text: 'Terjadwal Otomatis', class: 'bg-blue-100 text-blue-800' };
    return { text: 'Manual', class: 'bg-slate-100 text-slate-700' };
};

const createManualBackup = () => {
    createForm.post(route('backups.store'), {
        preserveScroll: true,
    });
};

const verifyBackup = (item) => {
    router.post(route('backups.verify', item.id), {}, { preserveScroll: true });
};

const openRestoreModal = (item) => {
    restoringItem.value = item;
    restoreLogForm.reset();
    restoreLogForm.clearErrors();
};

const submitRestoreFromLog = () => {
    if (!restoringItem.value) return;
    restoreLogForm.post(route('backups.restore-log', restoringItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            restoringItem.value = null;
            restoreLogForm.reset();
        },
    });
};

const handleUploadFile = (event) => {
    restoreUploadForm.backup_file = event.target.files[0] || null;
};

const submitRestoreFromUpload = () => {
    restoreUploadForm.post(route('backups.restore-upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            restoreUploadForm.reset();
            if (uploadFileInput.value) uploadFileInput.value.value = '';
        },
    });
};

const deleteBackup = (item) => {
    if (!confirm(`Hapus file arsip backup "${item.file_name}" secara permanen?`)) return;
    router.delete(route('backups.destroy', item.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Backup & Restore Database" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Backup & Restore Database</h2>
        </template>

        <div class="space-y-6">
            <!-- Panel Buat Backup Manual & Informasi Jadwal Otomatis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900">Cadangkan Database Sekarang (Manual)</h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Membuat salinan terkompresi (<code>.json.gz</code>) seluruh tabel transaksi, saldo awal, dokumentasi pembangunan, pengguna, dan log audit lengkap dengan verifikasi checksum <strong>SHA-256</strong>.
                        </p>
                        <p v-if="createForm.errors.backup" class="text-xs text-red-600 mt-2">{{ createForm.errors.backup }}</p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Jadwal otomatis: <strong>Setiap hari pukul 02:00 WIB</strong></span>
                        <button
                            type="button"
                            @click="createManualBackup"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Membuat Backup...' : 'Buat Backup Sekarang' }}
                        </button>
                    </div>
                </div>

                <!-- Panel Restore dari File Eksternal -->
                <div class="bg-white rounded-xl border border-amber-200 p-6">
                    <h3 class="font-bold text-slate-900">Restore Database dari File Eksternal</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-4">
                        Unggah file cadangan resmi (<code>.json.gz</code> / <code>.json</code>). Sistem akan otomatis membuat backup pengaman sebelum menimpa data.
                    </p>

                    <form @submit.prevent="submitRestoreFromUpload" class="space-y-3">
                        <div>
                            <input
                                ref="uploadFileInput"
                                type="file"
                                accept=".json,.gz"
                                required
                                @change="handleUploadFile"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100"
                            />
                            <p v-if="restoreUploadForm.errors.backup_file" class="text-xs text-red-600 mt-1">
                                {{ restoreUploadForm.errors.backup_file }}
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <input
                                v-model="restoreUploadForm.confirmation"
                                type="text"
                                required
                                placeholder="Ketik RESTORE untuk konfirmasi"
                                class="flex-1 rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500"
                            />
                            <button
                                type="submit"
                                :disabled="restoreUploadForm.processing"
                                class="px-4 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                {{ restoreUploadForm.processing ? 'Memulihkan...' : 'Unggah & Restore' }}
                            </button>
                        </div>
                        <p v-if="restoreUploadForm.errors.confirmation" class="text-xs text-red-600">
                            {{ restoreUploadForm.errors.confirmation }}
                        </p>
                    </form>
                </div>
            </div>

            <!-- Tabel Riwayat Backup -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Riwayat & Arsip Backup Database</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Waktu & Nama File</th>
                                <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                                <th class="px-4 py-3 text-left font-semibold">Ukuran</th>
                                <th class="px-4 py-3 text-center font-semibold">Status Integritas</th>
                                <th class="px-4 py-3 text-left font-semibold">Pemicu</th>
                                <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!backups?.data?.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada riwayat backup database. Klik "Buat Backup Sekarang" untuk membuat cadangan pertama.
                                </td>
                            </tr>
                            <tr v-for="item in backups?.data" :key="item.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3.5">
                                    <span class="block text-xs text-slate-500">{{ item.created_at }}</span>
                                    <span class="font-mono text-xs font-semibold text-slate-800">{{ item.file_name }}</span>
                                    <p v-if="item.error_message" class="text-[11px] text-red-600 mt-0.5">{{ item.error_message }}</p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span :class="typeBadge(item).class" class="px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                        {{ typeBadge(item).text }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-600">{{ item.file_size_human }}</td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <span
                                        :class="item.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ item.status === 'success' ? 'Terverifikasi' : 'Gagal / Korup' }}
                                    </span>
                                    <span v-if="item.verified_at" class="block text-[10px] text-slate-400 mt-0.5">{{ item.verified_at }}</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-600">{{ item.triggered_by_name }}</td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap space-x-1.5">
                                    <button
                                        v-if="item.file_exists"
                                        type="button"
                                        @click="verifyBackup(item)"
                                        class="px-2.5 py-1 rounded border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Verifikasi
                                    </button>
                                    <a
                                        v-if="item.file_exists"
                                        :href="route('backups.download', item.id)"
                                        class="inline-block px-2.5 py-1 rounded border border-emerald-200 bg-emerald-50 text-xs font-semibold text-[#008B45] hover:bg-emerald-100"
                                    >
                                        Unduh
                                    </a>
                                    <button
                                        v-if="item.file_exists && item.status === 'success'"
                                        type="button"
                                        @click="openRestoreModal(item)"
                                        class="px-2.5 py-1 rounded border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-800 hover:bg-amber-100"
                                    >
                                        Restore
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteBackup(item)"
                                        class="px-2.5 py-1 rounded border border-red-200 bg-red-50 text-xs font-semibold text-red-600 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="backups?.links?.length > 3" class="px-6 py-4 border-t border-slate-100 flex justify-end gap-1">
                    <template v-for="(link, idx) in backups.links" :key="idx">
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

        <!-- Modal Konfirmasi Restore -->
        <div v-if="restoringItem" @click.self="restoringItem = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <h3 class="text-lg font-bold text-amber-700">Konfirmasi Restore Database</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Seluruh data transaksi, saldo awal, progres pembangunan, dan pengaturan saat ini akan dikembalikan ke kondisi pada arsip:
                    <strong class="block font-mono text-slate-900 mt-1">{{ restoringItem.file_name }}</strong>
                </p>
                <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-900">
                    Sebelum restore dijalankan, sistem akan otomatis membuat salinan pengaman (<code>pre_restore</code>) terlebih dahulu.
                </div>

                <form @submit.prevent="submitRestoreFromLog" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Ketik <strong>RESTORE</strong> (huruf kapital) untuk melanjutkan *
                        </label>
                        <input
                            v-model="restoreLogForm.confirmation"
                            type="text"
                            required
                            placeholder="RESTORE"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                        />
                        <p v-if="restoreLogForm.errors.confirmation" class="text-xs text-red-600 mt-1">
                            {{ restoreLogForm.errors.confirmation }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="restoringItem = null"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="restoreLogForm.processing"
                            class="px-4 py-2 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ restoreLogForm.processing ? 'Memulihkan Database...' : 'Jalankan Restore Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>