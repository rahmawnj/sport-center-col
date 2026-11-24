<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Clock3, CheckCircle2 } from '@lucide/vue';
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
    filters: { status: string };
    summary: { today: number; upcoming: number; completed: number };
}>();

const filterStatus = (status: string) => router.get('/trainer/schedules', { status: status || undefined }, { preserveState: true, replace: true });
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