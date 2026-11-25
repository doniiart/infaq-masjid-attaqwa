<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stages: Array,
    documents: Object,
});

const activeTab = ref('documents');
const editingStageId = ref(null);
const editingDoc = ref(null);
const docFileInput = ref(null);
const editFileInput = ref(null);

const stageForm = useForm({
    title: '',
    description: '',
    stage_order: (props.stages?.length || 0) + 1,
    status: 'in_progress',
    progress_percentage: 0,
    start_date: '',
    end_date: '',
});

const docForm = useForm({
    construction_stage_id: '',
    title: '',
    description: '',
    documented_at: new Date().toISOString().slice(0, 10),
    is_published: true,
    photos: [],
});

const editDocForm = useForm({
    construction_stage_id: '',
    title: '',
    description: '',
    documented_at: '',
    is_published: true,
    new_photos: [],
});

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(dateStr + 'T00:00:00'));
};

const stageStatusBadge = (status) => {
    if (status === 'completed') return { text: 'Selesai', class: 'bg-emerald-100 text-emerald-800' };
    if (status === 'in_progress') return { text: 'Sedang Berjalan', class: 'bg-amber-100 text-amber-800' };
    return { text: 'Direncanakan', class: 'bg-slate-100 text-slate-700' };
};

const submitStage = () => {
    if (editingStageId.value) {
        stageForm.put(route('construction.stages.update', editingStageId.value), {
            preserveScroll: true,
            onSuccess: () => {
                editingStageId.value = null;
                stageForm.reset();
                stageForm.stage_order = (props.stages?.length || 0) + 1;
                stageForm.status = 'in_progress';
                stageForm.progress_percentage = 0;
            },
        });
    } else {
        stageForm.post(route('construction.stages.store'), {
            preserveScroll: true,
            onSuccess: () => {
                stageForm.reset();
                stageForm.stage_order = (props.stages?.length || 0) + 1;
                stageForm.status = 'in_progress';
                stageForm.progress_percentage = 0;
            },
        });
    }
};

const startEditStage = (stage) => {
    editingStageId.value = stage.id;
    stageForm.title = stage.title;
    stageForm.description = stage.description || '';
    stageForm.stage_order = stage.stage_order;
    stageForm.status = stage.status;
    stageForm.progress_percentage = stage.progress_percentage;
    stageForm.start_date = stage.start_date || '';
    stageForm.end_date = stage.end_date || '';
};

const cancelEditStage = () => {
    editingStageId.value = null;
    stageForm.reset();
    stageForm.stage_order = (props.stages?.length || 0) + 1;
    stageForm.status = 'in_progress';
    stageForm.progress_percentage = 0;
};

const deleteStage = (stage) => {
    if (!confirm(`Hapus tahapan "${stage.title}"? Dokumentasi terkait tetap tersimpan tanpa tahapan.`)) return;
    router.delete(route('construction.stages.destroy', stage.id), { preserveScroll: true });
};

const handlePhotosChange = (event) => {
    docForm.photos = Array.from(event.target.files || []);
};

const submitDocument = () => {
    docForm.post(route('construction.documents.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            docForm.reset('construction_stage_id', 'title', 'description', 'photos');
            docForm.documented_at = new Date().toISOString().slice(0, 10);
            docForm.is_published = true;
            if (docFileInput.value) docFileInput.value.value = '';
        },
    });
};

const openEditDocument = (doc) => {
    editingDoc.value = doc;
    editDocForm.construction_stage_id = doc.construction_stage_id || '';
    editDocForm.title = doc.title;
    editDocForm.description = doc.description || '';
    editDocForm.documented_at = doc.documented_at;
    editDocForm.is_published = doc.is_published;
    editDocForm.new_photos = [];
    editDocForm.clearErrors();
};

const handleEditPhotosChange = (event) => {
    editDocForm.new_photos = Array.from(event.target.files || []);
};

const submitEditDocument = () => {
    if (!editingDoc.value) return;
    editDocForm.post(route('construction.documents.update', editingDoc.value.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editingDoc.value = null;
            editDocForm.reset();
            if (editFileInput.value) editFileInput.value.value = '';
        },
    });
};

const deletePhoto = (photo) => {
    if (!confirm('Hapus foto ini secara permanen?')) return;
    router.delete(route('construction.photos.destroy', photo.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingDoc.value) {
                editingDoc.value.photos = editingDoc.value.photos.filter((p) => p.id !== photo.id);
            }
        },
    });
};

const deleteDocument = (doc) => {
    if (!confirm(`Hapus dokumentasi "${doc.title}" beserta seluruh fotonya?`)) return;
    router.delete(route('construction.documents.destroy', doc.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Dokumentasi & Progres Pembangunan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Dokumentasi & Progres Pembangunan</h2>
        </template>

        <div class="space-y-6">
            <!-- Tab Navigasi -->
            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <button
                    type="button"
                    @click="activeTab = 'documents'"
                    :class="activeTab === 'documents' ? 'bg-[#008B45] text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                >
                    Galeri Foto Dokumentasi ({{ documents?.total || 0 }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'stages'"
                    :class="activeTab === 'stages' ? 'bg-[#008B45] text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                >
                    Timeline & Tahapan Pembangunan ({{ stages?.length || 0 }})
                </button>
            </div>

            <!-- TAB 1: GALERI FOTO DOKUMENTASI -->
            <div v-if="activeTab === 'documents'" class="space-y-6">
                <!-- Form Unggah Dokumentasi Baru -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900">Unggah Dokumentasi Progres Baru</h3>
                    <p class="text-xs text-slate-500 mt-0.5 mb-5">
                        Anda dapat memilih beberapa foto sekaligus (JPG, PNG, WEBP — maksimal 5 MB per foto).
                    </p>

                    <form @submit.prevent="submitDocument" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Dokumentasi *</label>
                            <input
                                v-model="docForm.title"
                                type="text"
                                required
                                placeholder="Contoh: Pengecoran Tiang Kolom Utama"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            />
                            <p v-if="docForm.errors.title" class="text-xs text-red-600 mt-1">{{ docForm.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Dokumentasi *</label>
                            <input
                                v-model="docForm.documented_at"
                                type="date"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            />
                            <p v-if="docForm.errors.documented_at" class="text-xs text-red-600 mt-1">{{ docForm.errors.documented_at }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahapan Pembangunan Terkait (Opsional)</label>
                            <select
                                v-model="docForm.construction_stage_id"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            >
                                <option value="">-- Tanpa Tahapan Spesifik --</option>
                                <option v-for="st in stages" :key="st.id" :value="st.id">
                                    Tahap {{ st.stage_order }}: {{ st.title }} ({{ st.progress_percentage }}%)
                                </option>
                            </select>
                            <p v-if="docForm.errors.construction_stage_id" class="text-xs text-red-600 mt-1">{{ docForm.errors.construction_stage_id }}</p>
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="docForm.is_published"
                                    class="rounded border-slate-300 text-[#008B45] focus:ring-[#008B45]"
                                />
                                <span>Publikasikan langsung di halaman beranda publik</span>
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kegiatan / Progres</label>
                            <textarea
                                v-model="docForm.description"
                                rows="2"
                                placeholder="Keterangan pekerjaan fisik yang telah dilaksanakan..."
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            ></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Foto Dokumentasi (Bisa Lebih dari 1 Foto) *</label>
                            <input
                                ref="docFileInput"
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.webp"
                                required
                                @change="handlePhotosChange"
                                class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-[#008B45] hover:file:bg-emerald-100"
                            />
                            <p v-if="docForm.photos.length" class="text-xs text-emerald-700 mt-1">
                                {{ docForm.photos.length }} foto dipilih siap diunggah.
                            </p>
                            <p v-if="docForm.errors.photos" class="text-xs text-red-600 mt-1">{{ docForm.errors.photos }}</p>
                            <p v-if="docForm.errors['photos.0']" class="text-xs text-red-600 mt-1">{{ docForm.errors['photos.0'] }}</p>
                        </div>

                        <div class="md:col-span-2 flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="docForm.processing"
                                class="px-6 py-2.5 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                            >
                                {{ docForm.processing ? 'Mengunggah Foto...' : 'Simpan Dokumentasi' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Galeri Dokumentasi -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-4">Daftar Dokumentasi Tersimpan</h3>

                    <div v-if="!documents?.data?.length" class="text-center py-10 text-sm text-slate-500 border border-dashed border-slate-200 rounded-xl">
                        Belum ada dokumentasi pembangunan yang diunggah.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div
                            v-for="doc in documents.data"
                            :key="doc.id"
                            class="rounded-xl border border-slate-200 overflow-hidden flex flex-col justify-between bg-white"
                        >
                            <div>
                                <div class="aspect-video bg-slate-100 relative overflow-hidden">
                                    <img
                                        v-if="doc.photos?.length"
                                        :src="doc.photos[0].url"
                                        :alt="doc.title"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400">
                                        Tidak ada foto
                                    </div>
                                    <span
                                        :class="doc.is_published ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-white'"
                                        class="absolute top-2 left-2 px-2 py-0.5 rounded text-[11px] font-semibold"
                                    >
                                        {{ doc.is_published ? 'Publik' : 'Draft / Tersembunyi' }}
                                    </span>
                                    <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-slate-900/75 text-white text-[11px]">
                                        {{ doc.photos?.length || 0 }} foto
                                    </span>
                                </div>

                                <div class="p-4">
                                    <p class="text-xs text-slate-500">{{ formatDateIndo(doc.documented_at) }}</p>
                                    <h4 class="font-bold text-slate-900 text-sm mt-1">{{ doc.title }}</h4>
                                    <p v-if="doc.stage_title" class="text-xs text-[#008B45] font-medium mt-0.5">
                                        Tahap: {{ doc.stage_title }}
                                    </p>
                                    <p v-if="doc.description" class="text-xs text-slate-600 mt-2 line-clamp-2">
                                        {{ doc.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center text-xs">
                                <span class="text-slate-400">Oleh: {{ doc.creator_name }}</span>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        @click="openEditDocument(doc)"
                                        class="px-2.5 py-1 rounded border border-slate-300 bg-white font-semibold text-slate-700 hover:border-[#008B45] hover:text-[#008B45]"
                                    >
                                        Kelola / Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteDocument(doc)"
                                        class="px-2.5 py-1 rounded border border-red-200 bg-red-50 font-semibold text-red-600 hover:bg-red-100"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="documents?.links?.length > 3" class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-1">
                        <template v-for="(link, idx) in documents.links" :key="idx">
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

            <!-- TAB 2: TAHAPAN & TIMELINE PROGRES -->
            <div v-if="activeTab === 'stages'" class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-4">
                        {{ editingStageId ? 'Edit Tahapan Pembangunan' : 'Tambah Tahapan Pembangunan Baru' }}
                    </h3>

                    <form @submit.prevent="submitStage" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Tahapan Pekerjaan *</label>
                            <input
                                v-model="stageForm.title"
                                type="text"
                                required
                                placeholder="Contoh: Tahap I - Pekerjaan Pondasi & Beton Bertulang"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            />
                            <p v-if="stageForm.errors.title" class="text-xs text-red-600 mt-1">{{ stageForm.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Urutan Tahapan *</label>
                            <input
                                v-model="stageForm.stage_order"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Progres *</label>
                            <select
                                v-model="stageForm.status"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            >
                                <option value="planned">Direncanakan</option>
                                <option value="in_progress">Sedang Berjalan</option>
                                <option value="completed">Selesai (100%)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Persentase Progres (0 - 100%) *</label>
                            <input
                                v-model="stageForm.progress_percentage"
                                type="number"
                                min="0"
                                max="100"
                                :disabled="stageForm.status === 'completed'"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45] disabled:bg-slate-100"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai (Opsional)</label>
                            <input
                                v-model="stageForm.start_date"
                                type="date"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Tahapan</label>
                            <textarea
                                v-model="stageForm.description"
                                rows="2"
                                placeholder="Rincian pekerjaan pada tahapan ini..."
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                            ></textarea>
                        </div>

                        <div class="md:col-span-3 flex justify-end gap-2">
                            <button
                                v-if="editingStageId"
                                type="button"
                                @click="cancelEditStage"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="stageForm.processing"
                                class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037]"
                            >
                                {{ editingStageId ? 'Simpan Perubahan Tahapan' : 'Simpan Tahapan' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Tahapan -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="font-bold text-slate-900">Urutan Timeline Pembangunan</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-if="!stages?.length" class="p-8 text-center text-sm text-slate-500">
                            Belum ada tahapan pembangunan.
                        </div>
                        <div
                            v-for="st in stages"
                            :key="st.id"
                            class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50"
                        >
                            <div class="space-y-1.5 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-800 text-xs font-bold">#{{ st.stage_order }}</span>
                                    <h4 class="font-bold text-slate-900 text-sm">{{ st.title }}</h4>
                                    <span :class="stageStatusBadge(st.status).class" class="px-2 py-0.5 rounded text-[11px] font-semibold">
                                        {{ stageStatusBadge(st.status).text }}
                                    </span>
                                </div>
                                <p v-if="st.description" class="text-xs text-slate-600">{{ st.description }}</p>
                                <div class="max-w-md pt-1">
                                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                                        <span>Progres: <strong>{{ st.progress_percentage }}%</strong></span>
                                        <span>{{ st.documents_count }} dokumentasi</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2">
                                        <div class="bg-[#008B45] h-2 rounded-full" :style="{ width: st.progress_percentage + '%' }"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="startEditStage(st)"
                                    class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:border-[#008B45] hover:text-[#008B45]"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteStage(st)"
                                    class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-600 hover:bg-red-100"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Kelola / Edit Dokumentasi & Foto -->
        <div v-if="editingDoc" @click.self="editingDoc = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto space-y-5">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900">Kelola Dokumentasi Pembangunan</h3>
                    <button @click="editingDoc = null" class="text-slate-400 hover:text-slate-700 text-xl">&times;</button>
                </div>

                <!-- Foto yang Sudah Ada -->
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">Foto Terunggah ({{ editingDoc.photos?.length || 0 }})</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                        <div v-for="photo in editingDoc.photos" :key="photo.id" class="relative group rounded-lg overflow-hidden border border-slate-200 aspect-square">
                            <img :src="photo.url" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="deletePhoto(photo)"
                                class="absolute top-1 right-1 px-2 py-0.5 rounded bg-red-600 text-white text-[10px] font-bold shadow hover:bg-red-700"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitEditDocument" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Dokumentasi *</label>
                            <input v-model="editDocForm.title" type="text" required class="w-full rounded-lg border-slate-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                            <input v-model="editDocForm.documented_at" type="date" required class="w-full rounded-lg border-slate-300 text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahapan Terkait</label>
                        <select v-model="editDocForm.construction_stage_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- Tanpa Tahapan Spesifik --</option>
                            <option v-for="st in stages" :key="st.id" :value="st.id">
                                Tahap {{ st.stage_order }}: {{ st.title }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi</label>
                        <textarea v-model="editDocForm.description" rows="2" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tambah Foto Baru (Opsional)</label>
                        <input
                            ref="editFileInput"
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.webp"
                            @change="handleEditPhotosChange"
                            class="w-full text-xs text-slate-600"
                        />
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="editDocForm.is_published" class="rounded border-slate-300 text-[#008B45]" />
                            <span>Tampilkan di halaman publik</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="editingDoc = null" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">
                            Tutup
                        </button>
                        <button type="submit" :disabled="editDocForm.processing" class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037]">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>