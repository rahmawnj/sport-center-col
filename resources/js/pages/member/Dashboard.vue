<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CalendarCheck2,
    CalendarDays,
    Clock3,
    CreditCard,
    Plus,
    Trophy,
} from '@lucide/vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

const summaries = [
    { label: 'Booking mendatang', value: '0', note: 'Jadwal yang sudah kamu pesan', icon: CalendarCheck2, tone: 'lime' },
    { label: 'Booking selesai', value: '0', note: 'Total sesi olahraga selesai', icon: Clock3, tone: 'blue' },
    { label: 'Membership aktif', value: 'Belum ada', note: 'Paket keanggotaan kamu', icon: Trophy, tone: 'violet' },
    { label: 'Total pembayaran', value: 'Rp0', note: 'Riwayat transaksi kamu', icon: CreditCard, tone: 'orange' },
];
</script>

<template>
    <Head title="Dashboard Member" />
    <div class="space-y-7">
        <section class="relative isolate overflow-hidden rounded-3xl bg-[#001428] p-6 text-white sm:p-8 lg:p-10">
            <div class="absolute -right-10 -top-20 -z-10 size-72 rounded-full bg-[#a4da01]/20 blur-3xl" />
            <div class="absolute bottom-0 right-1/4 -z-10 size-40 rounded-full bg-sky-400/10 blur-3xl" />
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[.2em] text-[#b9ec35]">Member dashboard</p>
                <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ $page.props.auth?.user?.name || 'Member' }}! 👋</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">Siap berolahraga hari ini? Pantau booking, membership, dan aktivitas olahraga kamu dari satu tempat.</p>
                <a href="/book" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#a4da01] px-5 py-3 text-sm font-bold text-[#001428] transition hover:-translate-y-0.5 hover:bg-[#b8ec24]">
                    <Plus class="size-4" /> Buat booking
                    <ArrowUpRight class="size-4" />
                </a>
            </div>
            <div class="absolute bottom-0 right-7 hidden opacity-20 md:block">
                <Trophy class="size-40" :stroke-width="1" />
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="item in summaries" :key="item.label" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,.03)] transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ item.label }}</p>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ item.value }}</p>
                    </div>
                    <div class="grid size-11 place-items-center rounded-xl" :class="{
                        'bg-lime-100 text-lime-800': item.tone === 'lime',
                        'bg-sky-100 text-sky-700': item.tone === 'blue',
                        'bg-violet-100 text-violet-700': item.tone === 'violet',
                        'bg-orange-100 text-orange-700': item.tone === 'orange',
                    }">
                        <component :is="item.icon" class="size-5" />
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-400">{{ item.note }}</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,.03)] sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Booking terbaru</h2>
                        <p class="mt-1 text-sm text-slate-500">Pantau jadwal olahraga kamu</p>
                    </div>
                    <CalendarDays class="size-5 text-slate-400" />
                </div>
                <div class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-5 py-10 text-center">
                    <div class="grid size-14 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm">
                        <CalendarDays class="size-6" />
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-800">Belum ada booking</h3>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">Booking lapangan favoritmu dan jadwalnya akan muncul di sini.</p>
                    <a href="/book" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-lime-300 hover:bg-lime-50">
                        Cari jadwal <ArrowUpRight class="size-4" />
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,.03)] sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Membership saya</h2>
                        <p class="mt-1 text-sm text-slate-500">Status paket keanggotaan</p>
                    </div>
                    <Trophy class="size-5 text-slate-400" />
                </div>
                <div class="mt-6 rounded-2xl bg-gradient-to-br from-[#f5fbdc] to-[#f8fafc] p-5">
                    <span class="inline-flex rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-600">Belum berlangganan</span>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">Mulai jadi member</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Pilih paket membership yang sesuai dengan rutinitas olahraga kamu.</p>
                    <a href="/membership" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Lihat paket <ArrowUpRight class="size-4" />
                    </a>
                </div>
                <div class="mt-5 flex items-center gap-3 rounded-xl border border-slate-100 p-4">
                    <div class="grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-600"><Clock3 class="size-5" /></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Aktivitas akun</p>
                        <p class="mt-1 text-xs text-slate-500">Dashboard member siap digunakan</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
