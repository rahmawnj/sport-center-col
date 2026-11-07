<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';
import { ArrowLeft, CalendarDays, CheckCircle2, Clock3, Dumbbell, UserRound } from 'lucide-vue-next';

defineOptions({ layout: TrainerDashboardLayout });

type Item = {
    id: number;
    member: string;
    email: string | null;
    package: string;
    facility: string | null;
    start_date: string | null;
    expires_at: string | null;
    total_sessions: number;
    used_sessions: number;
    remaining_sessions: number;
    status: string;
};

type Schedule = {
    id: number;
    date: string | null;
    start_time: string;
    end_time: string;
    status: string;
};

defineProps<{ item: Item; schedules: Schedule[] }>();

const date = (value: string | null) =>
    value
        ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'long',
              year: 'numeric',
          })
        : '—';

const statusLabel = (status: string) =>
    ({
        active: 'Aktif',
        scheduled: 'Menunggu giliran',
        pending: 'Menunggu pembayaran',
        completed: 'Selesai',
        expired: 'Kedaluwarsa',
        cancelled: 'Dibatalkan',
        failed: 'Tidak berhasil',
    })[status] ?? status;

const statusClass = (status: string) => ({
    'bg-emerald-50 text-emerald-700': status === 'active' || status === 'completed',
    'bg-sky-50 text-sky-700': status === 'scheduled',
    'bg-amber-50 text-amber-700': status === 'pending',
    'bg-rose-50 text-rose-700': status === 'failed' || status === 'cancelled',
    'bg-slate-100 text-slate-600': status === 'expired',
});
</script>

<template>
    <Head :title="'Detail Member · ' + item.member" />

    <div class="space-y-6">
        <header class="flex items-center gap-3">
            <Link href="/trainer/members" class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" aria-label="Kembali ke member binaan">
                <ArrowLeft class="size-4" />
            </Link>
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Personal training</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Detail Member Binaan</h1>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div class="flex items-start gap-3">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-lime-50 text-lime-700">
                        <UserRound class="size-6" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Member</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">{{ item.member }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ item.email || 'Email tidak tersedia' }}</p>
                    </div>
                </div>
                <span class="w-fit rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClass(item.status)">
                    {{ statusLabel(item.status) }}
                </span>
            </div>

            <div class="mt-6 grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs text-slate-400">Paket trainer</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ item.package }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Fasilitas / olahraga</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ item.facility || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Mulai paket</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ date(item.start_date) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400">Paket berakhir</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ date(item.expires_at) }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-slate-500"><Dumbbell class="size-4" /> Total sesi</div>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ item.total_sessions }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-slate-500"><CheckCircle2 class="size-4" /> Sesi terpakai</div>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ item.used_sessions }}</p>
            </div>
            <div class="rounded-2xl border border-lime-200 bg-lime-50/60 p-5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-lime-800"><CalendarDays class="size-4" /> Sisa sesi</div>
                <p class="mt-3 text-3xl font-bold text-lime-800">{{ item.remaining_sessions }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Riwayat Jadwal Sesi</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar jadwal latihan untuk paket member ini.</p>
            </div>

            <div v-if="schedules.length" class="divide-y divide-slate-100">
                <article v-for="schedule in schedules" :key="schedule.id" class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                            <CalendarDays class="size-5" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">{{ date(schedule.date) }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                                <Clock3 class="size-3.5" /> {{ schedule.start_time }} – {{ schedule.end_time }}
                            </p>
                        </div>
                    </div>
                    <span class="w-fit rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClass(schedule.status)">
                        {{ statusLabel(schedule.status) }}
                    </span>
                </article>
            </div>
            <div v-else class="p-10 text-center">
                <CalendarDays class="mx-auto size-8 text-slate-300" />
                <h3 class="mt-3 font-semibold text-slate-800">Belum ada jadwal sesi</h3>
                <p class="mt-1 text-sm text-slate-500">Jadwal latihan member akan muncul di sini.</p>
            </div>
        </section>
    </div>
</template>
