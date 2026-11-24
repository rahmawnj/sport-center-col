<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Dumbbell, Users, Wallet, ArrowRight } from '@lucide/vue';
import TrainerDashboardLayout from '@/layouts/trainer/TrainerDashboardLayout.vue';

defineOptions({ layout: TrainerDashboardLayout });

type Schedule = {
    id: number; member: string; package: string; date: string;
    start_time: string; end_time: string;
    member_confirmed_at: string | null; trainer_confirmed_at: string | null;
};

const props = defineProps<{
    trainer: { name: string; specialty: string | null; facilities: string[] };
    stats: {
        total_members: number; active_members: number; remaining_sessions: number;
        earned_income: number; potential_income: number;
    };
    upcoming_schedules: Schedule[];
}>();

const money = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value || 0);

const date = (value: string) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
});
</script>

<template>
    <Head title="Dashboard Trainer" />
    <div class="space-y-7">
        <section class="relative isolate overflow-hidden rounded-3xl bg-[#001428] p-6 text-white sm:p-8 lg:p-10">
            <div class="absolute -right-10 -top-20 -z-10 size-72 rounded-full bg-[#a4da01]/20 blur-3xl" />
            <p class="text-xs font-bold uppercase tracking-[.2em] text-[#b9ec35]">Ringkasan pekerjaan</p>
            <h1 class="mt-3 text-2xl font-bold sm:text-3xl">Halo, {{ props.trainer.name }}!</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Pantau jadwal latihan yang dibuat admin, konfirmasi kehadiran, lihat member binaan, dan cek pendapatan dari sesi yang sudah berhasil.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span v-if="props.trainer.specialty" class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold">{{ props.trainer.specialty }}</span>
                <span v-for="facility in props.trainer.facilities" :key="facility" class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs">{{ facility }}</span>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Pendapatan terkumpul</p><Wallet class="size-5 text-emerald-700" /></div>
                <p class="mt-3 text-2xl font-bold text-emerald-700">{{ money(props.stats.earned_income) }}</p>
                <p class="mt-2 text-xs text-slate-500">Bagian trainer dari sesi yang sudah ditandai berhasil oleh admin.</p>
                <p class="mt-1 text-xs text-slate-400">Potensi total seluruh paket: {{ money(props.stats.potential_income) }}</p>
            </article>
            <article v-for="item in [
                { label: 'Total member binaan', value: props.stats.total_members, icon: Users, note: 'Member yang pernah ditugaskan kepadamu' },
                { label: 'Member aktif', value: props.stats.active_members, icon: Users, note: 'Member dengan paket aktif' },
                { label: 'Sisa kuota sesi', value: props.stats.remaining_sessions, icon: Dumbbell, note: 'Kuota dari paket yang masih aktif' },
            ]" :key="item.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">{{ item.label }}</p><component :is="item.icon" class="size-5 text-lime-700" /></div>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ item.value }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ item.note }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-bold text-slate-900">Jadwal latihan terdekat</h2><p class="mt-1 text-sm text-slate-500">Jadwal ditambahkan dan diatur oleh admin. Kamu bisa membuka detail untuk konfirmasi kehadiran.</p></div>
                <Link href="/trainer/schedules" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Semua jadwal <ArrowRight class="size-4" /></Link>
            </div>
            <div v-if="props.upcoming_schedules.length" class="divide-y divide-slate-100">
                <Link v-for="item in props.upcoming_schedules" :key="item.id" :href="'/trainer/schedules/' + item.id" class="flex flex-col gap-3 p-5 transition hover:bg-lime-50/40 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold text-slate-900">{{ item.member }}</p><p class="mt-1 text-sm text-slate-500">{{ item.package }} · {{ date(item.date) }}</p><p class="mt-1 text-sm font-medium text-slate-700">{{ item.start_time }}–{{ item.end_time }}</p></div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full px-3 py-1.5 font-semibold" :class="item.trainer_confirmed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">{{ item.trainer_confirmed_at ? 'Kehadiran dikonfirmasi' : 'Perlu konfirmasi kamu' }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 font-semibold text-slate-600">{{ item.member_confirmed_at ? 'Member sudah konfirmasi' : 'Menunggu member' }}</span>
                    </div>
                </Link>
            </div>
            <div v-else class="p-10 text-center"><CalendarDays class="mx-auto size-9 text-slate-300" /><p class="mt-3 font-semibold text-slate-800">Belum ada jadwal mendatang</p><p class="mt-1 text-sm text-slate-500">Jadwal yang dibuat admin akan muncul di bagian ini.</p></div>
        </section>

section>
    </div>
</template>