<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type PricingRate = {
    rental_type: string;
    price: number | string;
    unit_type: string;
    min_booking_duration: number;
};

type Space = {
    id: number;
    name: string;
    capacity: number;
    status: string;
    facilities: string[];
    pricing_rates: PricingRate[];
};

type Zone = {
    id: number;
    name: string;
    pricing_model: string;
    is_online_bookable: boolean;
    spaces: Space[];
};

defineProps<{ zone: Zone }>();

function formatPrice(value: number | string) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function unitLabel(unit: string) {
    const labels: Record<string, string> = {
        per_hour: 'per jam',
        per_visit: 'per kunjungan',
        per_session: 'per sesi',
    };
    return labels[unit] ?? '';
}
</script>

<template>
    <Head>
        <title>{{ zone.name }} | Fasilitas & Harga Booking Sport Center</title>
        <meta head-key="description" name="description" :content="'Lihat fasilitas, ruang, kapasitas, dan harga ' + zone.name + ' di Sport Center. Cek ketersediaan jadwal dan booking online tanpa harus mendaftar sebagai member.'" />
        <meta head-key="robots" name="robots" content="index, follow, max-image-preview:large" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:title" property="og:title" :content="zone.name + ' | Fasilitas & Harga Booking Sport Center'" />
        <meta head-key="og:description" property="og:description" :content="'Cek fasilitas, ruang, dan harga ' + zone.name + '. Booking online dengan mudah tanpa daftar member.'" />
    </Head>

    <main class="min-h-screen bg-[#f6f7f2] text-[#15251f]">
        <header class="border-b border-black/5">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/logo.png" alt="Sport Center" class="h-11 w-11 rounded-xl object-contain" />
                    <span>
                        <span class="block text-lg font-black tracking-tight">sport<span class="text-[#6b8e23]">center.</span></span>
                        <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">Move your way</span>
                    </span>
                </Link>
                <Link href="/" class="rounded-full border border-[#d7ddcf] px-4 py-2.5 text-sm font-bold transition hover:bg-white">← Kembali</Link>
            </div>
        </header>

        <section class="mx-auto max-w-6xl px-5 pb-16 pt-10 sm:px-8 sm:pb-24 sm:pt-16">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#819b42]">Explore the space</p>
            <div class="mt-3 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div>
                    <h1 class="text-4xl font-black tracking-tight sm:text-5xl">{{ zone.name }}</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">Lihat fasilitas olahraga, pilihan ruang, kapasitas, dan harga sewa yang tersedia. Kamu bisa cek jadwal dan lanjut booking online tanpa harus menjadi member.</p>
                </div>
                <span class="w-fit rounded-full bg-[#e7edda] px-4 py-2 text-xs font-extrabold text-[#53663a]">{{ zone.is_online_bookable ? 'Bisa dipesan online' : 'Informasi zona' }}</span>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-[#e4e8df] bg-white p-5">
                    <p class="text-sm font-medium text-slate-500">Total ruang</p>
                    <p class="mt-2 text-3xl font-black">{{ zone.spaces.length }}</p>
                </div>
                <div class="rounded-2xl border border-[#e4e8df] bg-white p-5">
                    <p class="text-sm font-medium text-slate-500">Ruang tersedia</p>
                    <p class="mt-2 text-3xl font-black">{{ zone.spaces.filter(space => space.status === 'available').length }}</p>
                </div>
                <div class="rounded-2xl border border-[#e4e8df] bg-white p-5">
                    <p class="text-sm font-medium text-slate-500">Model tarif</p>
                    <p class="mt-2 text-xl font-black">{{ ({ per_person: 'Per orang', per_space: 'Per ruang', per_trainer_session: 'Sesi pelatih', per_table: 'Per meja' } as Record<string, string>)[zone.pricing_model] ?? 'Tarif fleksibel' }}</p>
                </div>
            </div>

            <div class="mb-6 mt-12 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black tracking-tight sm:text-3xl">Ruang di zona ini</h2>
                    <p class="mt-2 text-sm text-slate-500">Pilih ruang untuk melihat fasilitas dan tarif yang tersedia.</p>
                </div>
            </div>

            <div v-if="zone.spaces.length" class="grid gap-4 sm:grid-cols-2">
                <article v-for="space in zone.spaces" :key="space.id" class="rounded-3xl border border-[#e4e8df] bg-white p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-extrabold">{{ space.name }}</h3>
                            <p class="mt-2 text-sm text-slate-500">Kapasitas {{ space.capacity }} orang</p>
                        </div>
                        <span class="rounded-full px-3 py-1.5 text-xs font-extrabold" :class="space.status === 'available' ? 'bg-[#edf6d9] text-[#526b2b]' : 'bg-amber-100 text-amber-800'">{{ space.status === 'available' ? 'Tersedia' : 'Maintenance' }}</span>
                    </div>

                    <div v-if="space.facilities.length" class="mt-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Fasilitas</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span v-for="facility in space.facilities" :key="facility" class="rounded-lg bg-[#f3f5ef] px-3 py-2 text-xs font-semibold text-slate-600">{{ facility }}</span>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-[#edf0e9] pt-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Tarif</p>
                        <div v-if="space.pricing_rates.length" class="mt-3 space-y-3">
                            <div v-for="rate in space.pricing_rates" :key="rate.rental_type" class="flex items-center justify-between gap-3 text-sm">
                                <div>
                                    <p class="font-bold">{{ rate.rental_type }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ unitLabel(rate.unit_type) }}<span v-if="rate.min_booking_duration > 1"> · minimal {{ rate.min_booking_duration }} unit</span></p>
                                </div>
                                <p class="shrink-0 font-black">{{ formatPrice(rate.price) }}</p>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">Tarif belum diatur.</p>
                        <Link v-if="zone.is_online_bookable && space.status === 'available' && space.pricing_rates.length" :href="`/book?zone_id=${zone.id}&space_id=${space.id}`" class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-[#172720] px-5 py-3 text-sm font-black text-white transition hover:bg-[#d8ff62] hover:text-[#172720]">Pilih ruang & booking ↗</Link>
                    </div>
                </article>
            </div>
            <div v-else class="rounded-3xl border border-dashed border-[#cbd3c1] bg-white p-10 text-center">
                <p class="text-lg font-bold">Belum ada ruang di zona ini</p>
                <p class="mt-2 text-sm text-slate-500">Ruang yang ditambahkan oleh pengelola akan muncul di sini.</p>
            </div>

            <div class="mt-10 rounded-3xl bg-[#172720] p-6 text-white sm:p-8">
                <h2 class="text-2xl font-black">Siap untuk mulai?</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/65">Pilih ruang dan jadwal yang tersedia, cek harga otomatis, lalu booking tanpa harus mendaftar sebagai member.</p>
                <Link v-if="zone.is_online_bookable" :href="`/book?zone_id=${zone.id}`" class="mt-5 inline-flex rounded-full bg-[#d8ff62] px-5 py-3 text-sm font-black text-[#172720] transition hover:bg-white">Booking zona ini ↗</Link>
                <Link v-else href="/" class="mt-5 inline-flex rounded-full bg-[#d8ff62] px-5 py-3 text-sm font-black text-[#172720] transition hover:bg-white">Lihat zona lainnya ↗</Link>
            </div>
        </section>
    </main>
</template>
