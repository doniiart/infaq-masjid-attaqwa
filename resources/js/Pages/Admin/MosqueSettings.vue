<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    setting: Object,
});

const logoPreview = ref(props.setting?.logo_url || '');

const form = useForm({
    name: props.setting?.name || '',
    address: props.setting?.address || '',
    email: props.setting?.email || '',
    phone: props.setting?.phone || '',
    description: props.setting?.description || '',
    logo: null,
});

const handleLogoChange = (event) => {
    const file = event.target.files[0] || null;
    form.logo = file;
    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submitSettings = () => {
    form.post(route('mosque-settings.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Profil & Informasi Masjid" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Pengaturan Profil & Informasi Masjid</h2>
        </template>

        <div class="max-w-3xl bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-900">Identitas Resmi Masjid At-Taqwa</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-6">
                Informasi ini ditampilkan pada halaman transparansi publik serta kop resmi laporan keuangan PDF.
            </p>

            <form @submit.prevent="submitSettings" class="space-y-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <img
                        v-if="logoPreview"
                        :src="logoPreview"
                        alt="Logo Masjid"
                        class="w-20 h-20 object-contain rounded-lg bg-white p-1.5 border border-slate-200"
                    />
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-700">Logo Resmi Masjid (JPG, PNG, WEBP — Maks 2 MB)</label>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            @change="handleLogoChange"
                            class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-[#008B45] hover:file:bg-emerald-100"
                        />
                        <p v-if="form.errors.logo" class="text-xs text-red-600">{{ form.errors.logo }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Masjid *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email Resmi</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Kontak / WhatsApp Panitia</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="Contoh: 0812-xxxx-xxxx"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Masjid *</label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        ></textarea>
                        <p v-if="form.errors.address" class="text-xs text-red-600 mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat Pembangunan Masjid</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Keterangan singkat program pembangunan Masjid At-Taqwa..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Masjid' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>