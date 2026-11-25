<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    users: Array,
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);
const editingUser = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    role: 'admin',
    is_active: true,
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email: '',
    role: 'admin',
    is_active: true,
    password: '',
    password_confirmation: '',
});

const submitCreate = () => {
    createForm.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'email', 'password', 'password_confirmation');
            createForm.role = 'admin';
            createForm.is_active = true;
        },
    });
};

const openEditModal = (user) => {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role;
    editForm.is_active = user.is_active;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.put(route('users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingUser.value = null;
            editForm.reset();
        },
    });
};

const toggleUserStatus = (user) => {
    const actionLabel = user.is_active ? 'nonaktifkan' : 'aktifkan';
    if (!confirm(`Yakin ingin meng-${actionLabel} akun pengurus "${user.name}"?`)) return;

    router.patch(route('users.toggle-status', user.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-lg text-slate-800">Manajemen Pengguna & Hak Akses</h2>
        </template>

        <div class="space-y-6">
            <!-- Form Tambah Akun Pengurus -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">Tambah Akun Pengurus Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-5">
                    Pendaftaran publik dinonaktifkan demi keamanan. Hanya Super Admin yang dapat membuat akun Admin atau Super Admin baru.
                </p>

                <form @submit.prevent="submitCreate" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="Contoh: Ahmad Fauzi"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="createForm.errors.name" class="text-xs text-red-600 mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email *</label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            required
                            placeholder="pengurus@masjidattaqwa.id"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="createForm.errors.email" class="text-xs text-red-600 mt-1">{{ createForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran (Role) *</label>
                        <select
                            v-model="createForm.role"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        >
                            <option value="admin">Admin (Bendahara / Panitia)</option>
                            <option value="super_admin">Super Admin (Akses Penuh)</option>
                        </select>
                        <p v-if="createForm.errors.role" class="text-xs text-red-600 mt-1">{{ createForm.errors.role }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi (Min. 8 Karakter) *</label>
                        <input
                            v-model="createForm.password"
                            type="password"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                        <p v-if="createForm.errors.password" class="text-xs text-red-600 mt-1">{{ createForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                        <input
                            v-model="createForm.password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div class="flex items-center pt-5">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="createForm.is_active"
                                class="rounded border-slate-300 text-[#008B45] focus:ring-[#008B45]"
                            />
                            <span>Akun langsung aktif</span>
                        </label>
                    </div>

                    <div class="md:col-span-3 flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-6 py-2.5 rounded-lg bg-[#008B45] text-white text-sm font-semibold hover:bg-[#007037] disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Buat Akun Pengurus' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Daftar Pengurus -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Daftar Akun Pengurus ({{ users?.length || 0 }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold">Nama</th>
                                <th class="px-5 py-3 text-left font-semibold">Email</th>
                                <th class="px-5 py-3 text-center font-semibold">Peran</th>
                                <th class="px-5 py-3 text-center font-semibold">Status</th>
                                <th class="px-5 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    {{ u.name }}
                                    <span v-if="u.id === currentUserId" class="ml-1.5 px-2 py-0.5 rounded bg-emerald-50 text-[#008B45] text-[11px] font-semibold">
                                        Anda
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">{{ u.email }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span
                                        :class="u.role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ u.role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span
                                        :class="u.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-700'"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                    >
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        @click="openEditModal(u)"
                                        class="px-3 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:border-[#008B45] hover:text-[#008B45]"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="u.id !== currentUserId"
                                        type="button"
                                        @click="toggleUserStatus(u)"
                                        :class="u.is_active ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100' : 'border-emerald-200 bg-emerald-50 text-[#008B45] hover:bg-emerald-100'"
                                        class="px-3 py-1 rounded-lg border text-xs font-semibold"
                                    >
                                        {{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Edit Pengguna -->
        <div v-if="editingUser" @click.self="editingUser = null" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900">Edit Akun Pengurus</h3>
                    <button @click="editingUser = null" class="text-slate-400 hover:text-slate-700 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input v-model="editForm.name" type="text" required class="w-full rounded-lg border-slate-300 text-sm" />
                        <p v-if="editForm.errors.name" class="text-xs text-red-600 mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email *</label>
                        <input v-model="editForm.email" type="email" required class="w-full rounded-lg border-slate-300 text-sm" />
                        <p v-if="editForm.errors.email" class="text-xs text-red-600 mt-1">{{ editForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran (Role) *</label>
                        <select v-model="editForm.role" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <p v-if="editForm.errors.role" class="text-xs text-red-600 mt-1">{{ editForm.errors.role }}</p>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" v-model="editForm.is_active" class="rounded border-slate-300 text-[#008B45]" />
                            <span>Status Akun Aktif</span>
                        </label>
                        <p v-if="editForm.errors.is_active" class="text-xs text-red-600 mt-1">{{ editForm.errors.is_active }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <p class="text-xs text-slate-500 mb-2">Kosongkan kolom kata sandi jika tidak ingin mengubah password.</p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru (Opsional)</label>
                                <input v-model="editForm.password" type="password" class="w-full rounded-lg border-slate-300 text-sm" />
                                <p v-if="editForm.errors.password" class="text-xs text-red-600 mt-1">{{ editForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                                <input v-model="editForm.password_confirmation" type="password" class="w-full rounded-lg border-slate-300 text-sm" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="editingUser = null" class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037]">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>