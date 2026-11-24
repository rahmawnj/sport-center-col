<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

const props = defineProps<{
    members: {
        data: {
            id: number;
            member: string;
            email: string | null;
            package: string;
            facility: string | null;
            start_date: string | null;
            expires_at: string | null;
            used_sessions: number;
            remaining_sessions: number;
            total_sessions: number;
            status: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        total?: number;
    };
}>();

const date = (value: string | null) =>
    value
        ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '—';

const initials = (name: string) =>
    name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

const progress = (item: (typeof props.members.data)[number]) =>
    item.total_sessions > 0 ? Math.min(100, Math.round((item.used_sessions / item.total_sessions) * 100)) : 0;

const statusLabel = (status: string) => ({
    active: 'Aktif',
    scheduled: 'Menunggu giliran',
    pending: 'Menunggu pembayaran',
    expired: 'Kedaluwarsa',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
}[status] ?? status);

const statusClass = (status: string) => ({
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
    scheduled: 'bg-sky-50 text-sky-700 ring-sky-600/15',
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/15',
    expired: 'bg-slate-100 text-slate-600 ring-slate-500/10',
    completed: 'bg-violet-50 text-violet-700 ring-violet-600/15',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-600/15',
}[status] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10');
</script>

<template>
    <Head title="Member Binaan" />

    <div class="space-y-7">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Personal training</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Member Binaan</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola dan pantau perkembangan member yang berlatih denganmu.</p>
            </div>
            <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 shadow-sm">
                <span class="size-2 rounded-full bg-lime-500"></span>
                <span class="font-semibold text-slate-900">{{ members.data.length }}</span>
                member di halaman ini
            </div>
        </header>

        <section>
            <div v-if="members.data.length" class="grid gap-4 xl:grid-cols-2">
                <Link
                    v-for="item in members.data"
                    :key="item.id"
                    :href="`/trainer/members/${item.id}`"
                    class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-lime-300 hover:shadow-lg hover:shadow-slate-200/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-lime-600"
                >
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-lime-400 via-lime-500 to-emerald-500 opacity-80"></div>

                    <div class="flex items-start gap-3">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-lime-50 text-sm font-extrabold tracking-wide text-lime-800 ring-1 ring-lime-100">
                            {{ initials(item.member) }}
                        </div>
                        <div class="min-w-0 flex-1 pt-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate font-bold text-slate-900">{{ item.member }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset" :class="statusClass(item.status)">
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ item.email || 'Email tidak tersedia' }}</p>
                        </div>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-400 transition group-hover:border-lime-200 group-hover:bg-lime-50 group-hover:text-lime-700" aria-hidden="true">↗</span>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Paket latihan</p>
                        <p class="mt-1 font-semibold text-slate-800">{{ item.package }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                            <span aria-hidden="true">⌖</span>
                            {{ item.facility || 'Cabang tidak ditentukan' }}
                        </p>
                    </div>

                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold text-slate-600">Progres sesi</p>
                            <p class="text-xs font-bold tabular-nums text-slate-800">{{ item.used_sessions }} / {{ item.total_sessions }} sesi</p>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-lime-400 to-emerald-500 transition-all" :style="{ width: progress(item) + '%' }"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ progress(item) }}% terselesaikan</span>
                            <span class="font-semibold text-slate-700">{{ item.remaining_sessions }} sesi tersisa</span>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Periode latihan</p>
                            <p class="mt-1 text-xs font-medium text-slate-700">{{ date(item.start_date) }} <span class="px-1 text-slate-400">—</span> {{ date(item.expires_at) }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-lime-700 transition group-hover:gap-2.5">
                            Lihat detail <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </Link>
            </div>

            <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-lime-50 text-2xl text-lime-700">♧</div>
                <h2 class="mt-4 font-bold text-slate-900">Belum ada member binaan</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Member yang mengambil paket latihan dan ditugaskan kepadamu akan muncul di sini.</p>
            </div>
        </section>

        <nav v-if="members.links.length > 3" class="flex flex-wrap items-center justify-center gap-2">
            <template v-for="link in members.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="min-w-9 rounded-xl border px-3 py-2 text-center text-sm transition"
                    :class="link.active ? 'border-lime-300 bg-lime-50 font-bold text-lime-800' : 'border-slate-200 bg-white text-slate-600 hover:border-lime-200 hover:bg-lime-50'"
                    v-html="link.label"
                />
                <span v-else class="min-w-9 rounded-xl border border-slate-100 px-3 py-2 text-center text-sm text-slate-300" v-html="link.label" />
            </template>
        </nav>
    </div>
</template>
