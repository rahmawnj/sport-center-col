<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CalendarDays, Clock3, CheckCircle2, CalendarRange, RotateCcw } from '@lucide/vue';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

type Schedule = {
    id: number; member: string; email: string | null; package: string; facility: string | null;
    date: string; start_time: string; end_time: string;
    member_confirmed_at: string | null; trainer_confirmed_at: string | null;
    member_attended: boolean; trainer_attended: boolean; status: string;
};
const props = defineProps<{
    schedules: { data: Schedule[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filters: { status: string; from: string | null; to: string | null };
    summary: { today: number; upcoming: number; completed: number };
}>();

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const rangeOpen = ref(false);
const rangeStart = ref(from.value);
const rangeEnd = ref(to.value);
const datePresets = [
    { value: 'today', label: 'Hari ini' },
    { value: '7days', label: '7 hari terakhir' },
    { value: '30days', label: '30 hari terakhir' },
    { value: 'month', label: 'Bulan ini' },
] as const;

function parseDate(value: string) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}
function toISO(value: Date) {
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
}
const viewMonth = ref(new Date((from.value || to.value || toISO(new Date())).slice(0, 7) + '-01T00:00:00'));
const monthViews = computed(() => [0, 1].map((offset) => {
    const month = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + offset, 1);
    const first = new Date(month.getFullYear(), month.getMonth(), 1);
    const gridStart = new Date(first);
    gridStart.setDate(first.getDate() - ((first.getDay() + 6) % 7));
    return {
        key: `${month.getFullYear()}-${month.getMonth()}`,
        title: new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(month),
        days: Array.from({ length: 42 }, (_, index) => {
            const day = new Date(gridStart);
            day.setDate(gridStart.getDate() + index);
            return { value: toISO(day), day: day.getDate(), inMonth: day.getMonth() === month.getMonth() };
        }),
    };
}));
const rangeLabel = computed(() => !from.value && !to.value ? 'Semua tanggal' : `${formatShort(from.value)} – ${formatShort(to.value)}`);
function formatShort(value: string) {
    return value ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parseDate(value)) : 'Pilih tanggal';
}
function shiftMonths(amount: number) {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + amount, 1);
}
function isInRange(value: string) {
    return !!rangeStart.value && !!rangeEnd.value && value >= rangeStart.value && value <= rangeEnd.value;
}
function isRangeEdge(value: string) {
    return value === rangeStart.value || value === rangeEnd.value;
}
function applyFilters(nextStatus = props.filters.status, nextFrom = from.value, nextTo = to.value) {
    router.get('/trainer/schedules', {
        status: nextStatus || undefined,
        from: nextFrom || undefined,
        to: nextTo || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}
function filterStatus(status: string) {
    applyFilters(status);
}
function chooseDay(value: string) {
    if (!rangeStart.value || rangeEnd.value) {
        rangeStart.value = value;
        rangeEnd.value = '';
        return;
    }
    if (value < rangeStart.value) {
        rangeStart.value = value;
        rangeEnd.value = '';
        return;
    }
    rangeEnd.value = value;
    from.value = rangeStart.value;
    to.value = rangeEnd.value;
    rangeOpen.value = false;
    applyFilters();
}
function applyPreset(preset: 'today' | '7days' | '30days' | 'month') {
    const end = new Date();
    const start = new Date(end);
    if (preset === '7days') start.setDate(end.getDate() - 6);
    if (preset === '30days') start.setDate(end.getDate() - 29);
    if (preset === 'month') start.setDate(1);
    from.value = toISO(start);
    to.value = toISO(end);
    rangeStart.value = from.value;
    rangeEnd.value = to.value;
    viewMonth.value = new Date(from.value.slice(0, 7) + '-01T00:00:00');
    rangeOpen.value = false;
    applyFilters();
}
function resetFilter() {
    from.value = '';
    to.value = '';
    rangeStart.value = '';
    rangeEnd.value = '';
    rangeOpen.value = false;
    applyFilters(props.filters.status, '', '');
}
const date = (value: string) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
const statusLabel = (status: string) => ({ scheduled: 'Terjadwal', completed: 'Berhasil', failed: 'Tidak berhasil' }[status] || status);
</script>

<template>
    <Head title="Jadwal Saya" />
    <div class="space-y-7">
        <header>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Personal training</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Jadwal Saya</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">Semua jadwal sesi yang dibuat dan diatur oleh admin untuk member binaanmu. Buka detail jadwal untuk melihat informasi dan mengonfirmasi kehadiran.</p>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Jadwal hari ini</p><p class="mt-2 text-3xl font-bold">{{ props.summary.today }}</p></article>
            <article class="rounded-2xl border bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Jadwal mendatang</p><p class="mt-2 text-3xl font-bold">{{ props.summary.upcoming }}</p></article>
            <article class="rounded-2xl border bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Sesi berhasil</p><p class="mt-2 text-3xl font-bold text-emerald-700">{{ props.summary.completed }}</p></article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2"><CalendarRange class="size-4 text-slate-500" /><h2 class="text-sm font-semibold text-slate-900">Filter rentang tanggal</h2></div>
                    <p class="mt-1 text-xs text-slate-500">Periode: {{ rangeLabel }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm hover:bg-slate-50" :aria-expanded="rangeOpen" @click="rangeOpen = !rangeOpen">
                            <CalendarRange class="size-4" /> {{ rangeLabel }}
                        </button>
                        <div v-if="rangeOpen" class="absolute right-0 z-30 mt-2 w-[min(680px,calc(100vw-2.5rem))] rounded-xl border border-slate-200 bg-white p-4 text-slate-900 shadow-xl">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold">{{ rangeLabel }}</p>
                                <button type="button" class="text-xs font-semibold text-lime-700 hover:underline" @click="resetFilter">Hapus rentang</button>
                            </div>
                            <div class="mb-4 flex flex-wrap gap-2">
                                <button v-for="preset in datePresets" :key="preset.value" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs hover:bg-slate-50" @click="applyPreset(preset.value)">{{ preset.label }}</button>
                            </div>
                            <div class="mb-3 flex items-center justify-between">
                                <button type="button" class="rounded-lg border px-3 py-1 text-sm hover:bg-slate-50" aria-label="Bulan sebelumnya" @click="shiftMonths(-1)">‹</button>
                                <p class="text-xs text-slate-500">Pilih tanggal awal, lalu tanggal akhir</p>
                                <button type="button" class="rounded-lg border px-3 py-1 text-sm hover:bg-slate-50" aria-label="Bulan berikutnya" @click="shiftMonths(1)">›</button>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div v-for="month in monthViews" :key="month.key" class="min-w-0">
                                    <p class="mb-3 text-center text-sm font-semibold capitalize">{{ month.title }}</p>
                                    <div class="mb-1 grid grid-cols-7 text-center text-xs text-slate-500">
                                        <span v-for="day in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="day" class="py-1">{{ day }}</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-y-1">
                                        <button v-for="day in month.days" :key="day.value" type="button" class="mx-auto flex size-8 items-center justify-center rounded-full text-xs transition-colors" :class="[!day.inMonth ? 'text-slate-300' : 'hover:bg-slate-100', isInRange(day.value) ? 'rounded-none bg-lime-100 text-lime-800' : '', isRangeEdge(day.value) ? 'rounded-full bg-lime-600 text-white hover:bg-lime-600' : '']" @click="chooseDay(day.value)">{{ day.day }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">{{ rangeStart ? formatShort(rangeStart) : 'Tanggal awal' }} – {{ rangeEnd ? formatShort(rangeEnd) : 'Tanggal akhir' }}</div>
                        </div>
                    </div>
                    <button type="button" class="inline-flex size-10 items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50" title="Reset filter" @click="resetFilter"><RotateCcw class="size-4" /></button>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-bold text-slate-900">Daftar jadwal latihan</h2><p class="mt-1 text-sm text-slate-500">{{ props.schedules.total }} jadwal tercatat</p></div>
                <select :value="props.filters.status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm" @change="filterStatus(($event.target as HTMLSelectElement).value)">
                    <option value="">Semua status</option>
                    <option value="scheduled">Terjadwal</option>
                    <option value="completed">Berhasil</option>
                    <option value="failed">Tidak berhasil</option>
                </select>
            </div>

            <div v-if="props.schedules.data.length" class="divide-y divide-slate-100">
                <Link v-for="item in props.schedules.data" :key="item.id" :href="'/trainer/schedules/' + item.id" class="grid gap-4 p-5 transition hover:bg-lime-50/40 md:grid-cols-[1.2fr_1fr_1fr_auto] md:items-center">
                    <div>
                        <p class="font-semibold text-slate-900">{{ item.member }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ item.email || 'Email tidak tersedia' }}</p>
                    </div>
                    <div><p class="text-xs text-slate-400">Paket / fasilitas</p><p class="mt-1 text-sm font-semibold">{{ item.package }}</p><p class="text-xs text-slate-500">{{ item.facility || '—' }}</p></div>
                    <div><p class="flex items-center gap-1.5 text-sm font-semibold text-slate-800"><CalendarDays class="size-4 text-slate-400" /> {{ date(item.date) }}</p><p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500"><Clock3 class="size-4" /> {{ item.start_time }}–{{ item.end_time }}</p></div>
                    <div class="flex flex-wrap items-center gap-2 md:justify-end">
                        <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="item.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : item.status === 'failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">{{ statusLabel(item.status) }}</span>
                        <span v-if="item.status === 'scheduled' && !item.trainer_confirmed_at" class="rounded-full bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">Konfirmasi diperlukan</span>
                        <span v-else-if="item.status === 'scheduled'" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"><CheckCircle2 class="size-3.5" /> Sudah konfirmasi</span>
                    </div>
                </Link>
            </div>
            <div v-else class="p-12 text-center"><CalendarDays class="mx-auto size-9 text-slate-300" /><p class="mt-3 font-semibold text-slate-800">Belum ada jadwal</p><p class="mt-1 text-sm text-slate-500">Jadwal yang dibuat admin akan muncul di sini.</p></div>
        </section>

        <nav v-if="props.schedules.links.length > 3" class="flex flex-wrap gap-2">
            <template v-for="link in props.schedules.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active ? 'border-lime-300 bg-lime-50 font-bold' : 'border-slate-200 bg-white text-slate-600'" v-html="link.label" />
                <span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" v-html="link.label" />
            </template>
        </nav>
    </div>
</template>