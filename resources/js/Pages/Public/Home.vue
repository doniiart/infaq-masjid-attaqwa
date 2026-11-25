<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    summary: Object,
    chart: Object,
    transactions: Object,
    filters: Object,
    stages: Array,
    documents: Array,
});

const page = usePage();
const mosque = computed(() => page.props.mosque);

const filterForm = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    date: props.filters?.date || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    chart_months: props.filters?.chart_months || 6,
});

const isLoading = ref(false);
const selectedTransaction = ref(null);
const selectedDocument = ref(null);
const activePhotoIndex = ref(0);

const formatRupiah = (amount) => {
    const num = Number(amount || 0);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr + 'T00:00:00');
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

const applyFilters = () => {
    isLoading.value = true;
    router.get(route('home'), filterForm, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.type = '';
    filterForm.date = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    applyFilters();
};

const changeChartPeriod = (months) => {
    filterForm.chart_months = months;
    applyFilters();
};

const maxChartValue = computed(() => {
    if (!props.chart?.series?.length) return 1000000;
    const maxVal = Math.max(
        ...props.chart.series.flatMap((item) => [Number(item.income), Number(item.expense)])
    );
    return maxVal > 0 ? maxVal : 1000000;
});

const getBarHeight = (val) => {
    const percentage = (Number(val || 0) / maxChartValue.value) * 100;
    return Math.max(percentage, val > 0 ? 4 : 0) + '%';
};

const stageStatusLabel = (status) => {
    if (status === 'completed') return { text: 'Selesai', class: 'bg-emerald-100 text-emerald-800' };
    if (status === 'in_progress') return { text: 'Sedang Berjalan', class: 'bg-amber-100 text-amber-800' };
    return { text: 'Direncanakan', class: 'bg-slate-100 text-slate-700' };
};

const openDocumentModal = (doc) => {
    selectedDocument.value = doc;
    activePhotoIndex.value = 0;
};
</script>

<template>
    <Head title="Beranda - Transparansi Infaq Pembangunan Masjid At-Taqwa" />

    <PublicLayout>
        <!-- Hero Identitas Masjid -->
        <section class="bg-gradient-to-br from-[#008B45] via-emerald-700 to-emerald-900 text-white py-14 sm:py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-emerald-100 text-xs font-semibold tracking-wide uppercase mb-4">
                        Transparansi Keuangan & Progres Pembangunan
                    </span>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">
                        Pembangunan {{ mosque?.name || 'Masjid At-Taqwa' }}
                    </h1>
                    <p class="mt-4 text-emerald-100 text-base sm:text-lg leading-relaxed">
                        {{ mosque?.about_description }}
                    </p>
                    <div class="mt-5 flex flex-wrap items-center gap-4 text-sm text-emerald-100">
                        <span>{{ mosque?.address }}</span>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 w-full lg:w-80 text-white">
                    <p class="text-xs uppercase tracking-wider text-emerald-200 font-semibold">Saldo Dana Tersedia Saat Ini</p>
                    <p class="text-2xl sm:text-3xl font-bold mt-2">{{ formatRupiah(summary?.available_balance) }}</p>
                    <p class="text-xs text-emerald-100 mt-3 leading-relaxed">
                        Dihitung otomatis dari Saldo Awal ditambah Total Pemasukan Terverifikasi dikurangi Total Pengeluaran Terverifikasi.
                    </p>
                </div>
            </div>
        </section>

        <!-- Ringkasan Keuangan -->
        <section id="ringkasan" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Saldo Awal Sistem</p>
                    <p class="text-2xl font-bold text-slate-800 mt-2">{{ formatRupiah(summary?.opening_balance) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Dana awal sebelum pencatatan sistem</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Total Pemasukan Infaq</p>
                    <p class="text-2xl font-bold text-[#008B45] mt-2">{{ formatRupiah(summary?.total_income) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Akumulasi infaq & donasi terverifikasi</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Total Pengeluaran</p>
                    <p class="text-2xl font-bold text-amber-600 mt-2">{{ formatRupiah(summary?.total_expense) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Realisasi biaya pembangunan terverifikasi</p>
                </div>

                <div class="bg-emerald-50 rounded-xl shadow-sm border border-emerald-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#008B45]">Saldo Dana Tersedia</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ formatRupiah(summary?.available_balance) }}</p>
                    <p class="text-xs text-emerald-700 mt-1">Saldo aktif siap digunakan pembangunan</p>
                </div>
            </div>
        </section>

        <!-- Grafik Pemasukan & Pengeluaran -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Grafik Pemasukan & Pengeluaran</h2>
                        <p class="text-sm text-slate-500 mt-1">Periode: {{ chart?.period_label }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="changeChartPeriod(6)"
                            :class="filterForm.chart_months === 6 ? 'bg-[#008B45] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition"
                        >
                            6 Bulan Terakhir
                        </button>
                        <button
                            type="button"
                            @click="changeChartPeriod(12)"
                            :class="filterForm.chart_months === 12 ? 'bg-[#008B45] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition"
                        >
                            12 Bulan Terakhir
                        </button>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex items-center gap-6 text-xs font-medium text-slate-600 mb-6">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm bg-[#008B45]"></span>
                        <span>Pemasukan Infaq</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm bg-amber-500"></span>
                        <span>Pengeluaran Pembangunan</span>
                    </div>
                </div>

                <!-- Bar Chart -->
                <div class="overflow-x-auto pb-2">
                    <div class="min-w-[540px] h-64 flex items-end justify-between gap-3 pt-6 px-2 border-b border-slate-200">
                        <div
                            v-for="item in chart?.series"
                            :key="item.label"
                            class="flex-1 flex flex-col items-center h-full justify-end group"
                        >
                            <div class="w-full flex items-end justify-center gap-1.5 h-52">
                                <div
                                    :style="{ height: getBarHeight(item.income) }"
                                    :title="`Pemasukan (${item.label}): ${formatRupiah(item.income)}`"
                                    class="w-4 sm:w-6 bg-[#008B45] rounded-t transition-all duration-300 hover:opacity-85"
                                ></div>
                                <div
                                    :style="{ height: getBarHeight(item.expense) }"
                                    :title="`Pengeluaran (${item.label}): ${formatRupiah(item.expense)}`"
                                    class="w-4 sm:w-6 bg-amber-500 rounded-t transition-all duration-300 hover:opacity-85"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div class="min-w-[540px] flex justify-between gap-3 px-2 pt-2">
                        <div
                            v-for="item in chart?.series"
                            :key="item.label + '-lbl'"
                            class="flex-1 text-center text-[11px] font-medium text-slate-500"
                        >
                            {{ item.label }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Riwayat Transaksi Publik -->
        <section id="transaksi" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Riwayat Transaksi Pembangunan</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Catatan pemasukan dan pengeluaran yang telah terverifikasi. Nama donatur yang dirahasiakan ditampilkan sebagai <strong>Hamba Allah</strong>.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="route('reports.pdf', { period_type: 'weekly' })"
                            class="px-3.5 py-2 rounded-lg border border-[#008B45] text-[#008B45] text-xs font-semibold hover:bg-emerald-50 transition"
                        >
                            Unduh PDF Mingguan
                        </a>
                        <a
                            :href="route('reports.pdf', { period_type: 'monthly' })"
                            class="px-4 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037] transition"
                        >
                            Unduh PDF Bulanan
                        </a>
                    </div>
                </div>

                <!-- Filter Bar -->
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Cari Keterangan / Donatur</label>
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Cari sumber, keperluan, donatur..."
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Transaksi</label>
                        <select
                            v-model="filterForm.type"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="income">Pemasukan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal Spesifik</label>
                        <input
                            v-model="filterForm.date"
                            type="date"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                        <input
                            v-model="filterForm.date_from"
                            type="date"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                        <input
                            v-model="filterForm.date_to"
                            type="date"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-[#008B45] focus:ring-[#008B45]"
                        />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center justify-end gap-2 pt-1">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-white transition"
                        >
                            Reset Filter
                        </button>
                        <button
                            type="submit"
                            :disabled="isLoading"
                            class="px-5 py-2 rounded-lg bg-[#008B45] text-white text-xs font-semibold hover:bg-[#007037] transition disabled:opacity-50"
                        >
                            {{ isLoading ? 'Memuat...' : 'Terapkan Filter' }}
                        </button>
                    </div>
                </form>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold">Sumber / Keperluan</th>
                                <th class="px-4 py-3 text-left font-semibold">Donatur</th>
                                <th class="px-4 py-3 text-right font-semibold">Nominal</th>
                                <th class="px-4 py-3 text-center font-semibold">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!transactions?.data?.length">
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    Belum ada data transaksi yang sesuai dengan filter saat ini.
                                </td>
                            </tr>
                            <tr
                                v-for="tx in transactions?.data"
                                :key="tx.id"
                                class="hover:bg-slate-50/80 transition"
                            >
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                                    {{ formatDateIndo(tx.transaction_date) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span
                                        :class="tx.type === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                    >
                                        {{ tx.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-800 font-medium">
                                    {{ tx.source_or_purpose }}
                                    <p v-if="tx.description" class="text-xs text-slate-500 font-normal line-clamp-1 mt-0.5">
                                        {{ tx.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-slate-600">
                                    {{ tx.donor_display }}
                                </td>
                                <td
                                    :class="tx.type === 'income' ? 'text-[#008B45]' : 'text-amber-600'"
                                    class="px-4 py-3.5 whitespace-nowrap text-right font-bold"
                                >
                                    {{ tx.type === 'income' ? '+' : '-' }} {{ formatRupiah(tx.amount) }}
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="selectedTransaction = tx"
                                        class="px-3 py-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:border-[#008B45] hover:text-[#008B45] transition"
                                    >
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions?.links?.length > 3" class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <p class="text-xs text-slate-500">
                        Menampilkan {{ transactions.from || 0 }} - {{ transactions.to || 0 }} dari {{ transactions.total }} transaksi
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, idx) in transactions.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                preserve-state
                                v-html="link.label"
                                :class="link.active ? 'bg-[#008B45] text-white border-[#008B45]' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg border border-slate-100 text-slate-400 text-xs"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timeline & Dokumentasi Progres Pembangunan -->
        <section id="progres" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Timeline Tahapan -->
                <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-slate-900">Timeline Progres Pembangunan</h2>
                    <p class="text-sm text-slate-500 mt-1 mb-6">Tahapan pelaksanaan fisik Masjid At-Taqwa.</p>

                    <div v-if="!stages?.length" class="text-sm text-slate-500 py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Tahapan pembangunan belum ditambahkan oleh pengurus.
                    </div>

                    <div v-else class="space-y-6 border-l-2 border-emerald-200 ml-3 pl-6">
                        <div v-for="stage in stages" :key="stage.id" class="relative">
                            <span
                                :class="stage.status === 'completed' ? 'bg-[#008B45]' : stage.status === 'in_progress' ? 'bg-amber-500' : 'bg-slate-300'"
                                class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full ring-4 ring-white"
                            ></span>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-bold text-slate-900 text-sm">{{ stage.title }}</h3>
                                <span
                                    :class="stageStatusLabel(stage.status).class"
                                    class="px-2 py-0.5 rounded text-[11px] font-semibold"
                                >
                                    {{ stageStatusLabel(stage.status).text }}
                                </span>
                            </div>
                            <p v-if="stage.description" class="text-xs text-slate-600 mt-1">{{ stage.description }}</p>
                            <div class="mt-2">
                                <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                                    <span>Progres</span>
                                    <span class="font-semibold">{{ stage.progress_percentage }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div
                                        class="bg-[#008B45] h-2 rounded-full transition-all"
                                        :style="{ width: stage.progress_percentage + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Galeri Dokumentasi -->
                <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-slate-900">Dokumentasi Pembangunan</h2>
                    <p class="text-sm text-slate-500 mt-1 mb-6">Foto lapangan perkembangan pembangunan masjid.</p>

                    <div v-if="!documents?.length" class="text-sm text-slate-500 py-12 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        Belum ada foto dokumentasi pembangunan yang dipublikasikan.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div
                            v-for="doc in documents"
                            :key="doc.id"
                            @click="openDocumentModal(doc)"
                            class="group cursor-pointer rounded-xl border border-slate-200 overflow-hidden hover:border-[#008B45] transition bg-slate-50"
                        >
                            <div class="aspect-video bg-slate-200 overflow-hidden relative">
                                <img
                                    v-if="doc.photos?.length"
                                    :src="doc.photos[0].url"
                                    :alt="doc.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-xs text-slate-400">
                                    Tanpa Foto
                                </div>
                                <span
                                    v-if="doc.photos?.length > 1"
                                    class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-slate-900/75 text-white text-[11px]"
                                >
                                    +{{ doc.photos.length - 1 }} foto
                                </span>
                            </div>
                            <div class="p-4 bg-white">
                                <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                    <span>{{ formatDateIndo(doc.documented_at) }}</span>
                                    <span v-if="doc.stage_title" class="text-[#008B45] font-medium">{{ doc.stage_title }}</span>
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm mt-1 group-hover:text-[#008B45]">{{ doc.title }}</h3>
                                <p v-if="doc.description" class="text-xs text-slate-600 mt-1 line-clamp-2">{{ doc.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Informasi Rekening Resmi (Hanya muncul jika sudah diisi & diverifikasi Super Admin) -->
        <section v-if="mosque?.bank_accounts?.length" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900">Saluran Infaq Pembangunan Resmi</h2>
                <p class="text-sm text-slate-600 mt-1">Rekening resmi yang telah dikonfirmasi oleh Takmir Masjid At-Taqwa:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <div
                        v-for="(acc, index) in mosque.bank_accounts"
                        :key="index"
                        class="bg-white rounded-xl p-4 border border-emerald-200"
                    >
                        <p class="text-xs font-bold uppercase text-[#008B45]">{{ acc.bank_name }}</p>
                        <p class="text-lg font-bold text-slate-900 mt-1 tracking-wide">{{ acc.account_number }}</p>
                        <p class="text-xs text-slate-600 mt-0.5">a.n. {{ acc.account_name }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal Detail Transaksi Publik -->
        <div
            v-if="selectedTransaction"
            @click.self="selectedTransaction = null"
            class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div>
                        <span class="text-xs font-mono text-slate-400">{{ selectedTransaction.transaction_code }}</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-0.5">Detail Transaksi</h3>
                    </div>
                    <button @click="selectedTransaction = null" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>

                <div class="py-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal</span>
                        <span class="font-medium text-slate-900">{{ formatDateIndo(selectedTransaction.transaction_date) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Jenis</span>
                        <span class="font-semibold">{{ selectedTransaction.type === 'income' ? 'Pemasukan Infaq' : 'Pengeluaran Pembangunan' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nominal</span>
                        <span class="font-bold text-base text-[#008B45]">{{ formatRupiah(selectedTransaction.amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Sumber / Keperluan</span>
                        <span class="font-medium text-slate-900 text-right">{{ selectedTransaction.source_or_purpose }}</span>
                    </div>
                    <div v-if="selectedTransaction.type === 'income'" class="flex justify-between">
                        <span class="text-slate-500">Donatur</span>
                        <span class="font-medium text-slate-900">{{ selectedTransaction.donor_display }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-100">
                        <span class="block text-slate-500 mb-1">Keterangan</span>
                        <p class="text-slate-800 bg-slate-50 p-3 rounded-lg">{{ selectedTransaction.description || '-' }}</p>
                    </div>
                    <div v-if="selectedTransaction.attachments?.length" class="pt-2">
                        <span class="block text-slate-500 mb-1">Lampiran Bukti Publik</span>
                        <div class="space-y-1">
                            <a
                                v-for="att in selectedTransaction.attachments"
                                :key="att.id"
                                :href="att.url"
                                target="_blank"
                                class="block text-xs text-[#008B45] underline hover:text-emerald-800"
                            >
                                {{ att.file_name }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="selectedTransaction = null"
                        class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Detail Dokumentasi Pembangunan -->
        <div
            v-if="selectedDocument"
            @click.self="selectedDocument = null"
            class="fixed inset-0 z-50 bg-slate-900/70 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden shadow-xl">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">{{ formatDateIndo(selectedDocument.documented_at) }}</p>
                        <h3 class="text-lg font-bold text-slate-900">{{ selectedDocument.title }}</h3>
                    </div>
                    <button @click="selectedDocument = null" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>

                <div v-if="selectedDocument.photos?.length" class="bg-slate-950 relative">
                    <img
                        :src="selectedDocument.photos[activePhotoIndex]?.url"
                        :alt="selectedDocument.title"
                        class="max-h-96 w-full object-contain mx-auto"
                    />
                    <div v-if="selectedDocument.photos.length > 1" class="flex justify-center gap-2 p-3 bg-slate-900">
                        <button
                            v-for="(photo, idx) in selectedDocument.photos"
                            :key="photo.id"
                            @click="activePhotoIndex = idx"
                            :class="activePhotoIndex === idx ? 'ring-2 ring-[#008B45]' : 'opacity-60'"
                            class="w-14 h-10 rounded overflow-hidden"
                        >
                            <img :src="photo.url" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <p class="text-sm text-slate-700 leading-relaxed">{{ selectedDocument.description || 'Tidak ada deskripsi tambahan.' }}</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>