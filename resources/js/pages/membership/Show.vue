<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

type MembershipPackage = {
    id: number;
    name: string;
    duration_days: number;
    facility: string | null;
    facility_slug: string | null;
    prices: number[];
    price: number | null;
    description: string;
};

defineProps<{ package: MembershipPackage }>();

const formatCurrency = (value: number | null) => value === null
    ? 'Harga belum tersedia'
    : new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const durationLabel = (days: number) => {
    if (days >= 365 && days % 365 === 0) return days / 365 + ' tahun';
    if (days >= 30 && days % 30 === 0) return days / 30 + ' bulan';
    return days + ' hari';
};
</script>

<template>
    <Head :title="package.name + ' — Membership DreamSports'" />
    <PublicLayout>
        <main class="min-h-screen bg-white text-[#001428]">
            <section class="relative overflow-hidden bg-[#001428]">
                <div class="pointer-events-none absolute -right-32 -top-56 h-[620px] w-[620px] rounded-full border border-[#a4da01]/20 shadow-[0_0_0_70px_rgba(164,218,1,.035),0_0_0_140px_rgba(164,218,1,.025)]"></div>
                <div class="relative mx-auto max-w-7xl px-5 py-16 sm:py-20 lg:px-8 lg:py-24">
                    <Link href="/membership" class="inline-flex items-center gap-2 text-sm font-bold text-slate-300 transition hover:text-[#b7e934]">
                        <i class="fa-solid fa-arrow-left"></i> Semua paket membership
                    </Link>
                    <div class="mt-12 grid items-center gap-12 lg:grid-cols-[1.1fr_.9fr]">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-[#b7e934]">{{ package.facility || 'DreamSports' }} · DETAIL MEMBERSHIP</p>
                            <h1 class="mt-4 max-w-2xl text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">{{ package.name }}</h1>
                            <p class="mt-6 max-w-xl text-base leading-7 text-slate-300">{{ package.description }}</p>
                            <div class="mt-9 flex flex-wrap gap-3">
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white">
                                    <i class="fa-regular fa-calendar text-[#b7e934]"></i> {{ durationLabel(package.duration_days) }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white">
                                    <i class="fa-solid fa-dumbbell text-[#b7e934]"></i> {{ package.facility || 'Membership olahraga' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-3xl border border-white/15 bg-white/[.07] p-7 shadow-2xl backdrop-blur sm:p-9">
                            <p class="text-xs font-extrabold uppercase tracking-[.18em] text-[#b7e934]">Harga membership</p>
                            <p class="mt-4 text-4xl font-black text-white">{{ formatCurrency(package.price) }}</p>
                            <p class="mt-2 text-sm text-slate-300">Masa berlaku {{ package.duration_days }} hari sejak aktivasi.</p>
                            <div class="my-7 h-px bg-white/15"></div>
                            <h2 class="font-extrabold text-white">Yang perlu kamu ketahui</h2>
                            <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
                                <li class="flex gap-3"><i class="fa-solid fa-circle-check mt-1 text-[#b7e934]"></i><span>Membership terhubung dengan akun member yang digunakan saat pembelian.</span></li>
                                <li class="flex gap-3"><i class="fa-solid fa-circle-check mt-1 text-[#b7e934]"></i><span>Periode membership mengikuti durasi paket dan jadwal aktivasi.</span></li>
                                <li class="flex gap-3"><i class="fa-solid fa-circle-check mt-1 text-[#b7e934]"></i><span>Status pembelian dan pembayaran dapat dipantau dari akun member.</span></li>
                            </ul>
                            <Link href="/membership#pilih-paket" class="mt-8 inline-flex w-full items-center justify-center gap-3 rounded-full bg-[#a4da01] px-6 py-4 font-extrabold text-[#001428] transition hover:-translate-y-0.5 hover:bg-[#b8ec24]">
                                Lihat pilihan & beli paket <i class="fa-solid fa-arrow-right"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
            <section class="mx-auto max-w-7xl px-5 py-12 lg:px-8">
                <div class="flex flex-col gap-4 rounded-3xl border border-[#e7edd7] bg-[#f7faef] p-7 sm:flex-row sm:items-center sm:justify-between sm:p-9">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#789900]">Mulai perjalananmu</p>
                        <h2 class="mt-2 text-2xl font-black">Siap lebih konsisten berolahraga?</h2>
                        <p class="mt-2 text-sm text-slate-500">Kembali ke daftar membership untuk membandingkan paket lainnya.</p>
                    </div>
                    <Link href="/membership" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#001428] px-6 py-3.5 font-bold text-white transition hover:bg-[#18334c]">Semua paket <i class="fa-solid fa-arrow-right"></i></Link>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>
