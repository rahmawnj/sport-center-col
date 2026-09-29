<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Users } from '@lucide/vue';

interface Space { id: number; name: string; capacity: number | null; }
interface Zone { id: number; name: string; pricing_model: string; zone_spaces: Space[]; }

defineProps<{ zones: Zone[] }>();

const heroImages = [
    'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=2200&q=85',
    'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=2200&q=85',
    'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=2200&q=85',
    'https://images.unsplash.com/photo-1622163642998-1ea32b0bbc67?auto=format&fit=crop&w=2200&q=85',
    'https://images.unsplash.com/photo-1775993167284-8e6a6e56ab69?auto=format&fit=crop&w=2200&q=85',
];

const heroImage = heroImages[Math.floor(Math.random() * heroImages.length)];

const imageForSport = (name: string) => {
    const value = name.toLowerCase();

    if (value.includes('badminton')) return 'https://images.unsplash.com/photo-1775993167284-8e6a6e56ab69?auto=format&fit=crop&w=1200&q=85';
    if (value.includes('basket')) return 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=85';
    if (value.includes('futsal') || value.includes('sepak bola') || value.includes('soccer')) return 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=1200&q=85';
    if (value.includes('tenis') || value.includes('tennis')) return 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=1200&q=85';
    if (value.includes('padel')) return 'https://images.unsplash.com/photo-1622163642998-1ea32b0bbc67?auto=format&fit=crop&w=1200&q=85';

    return 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=1200&q=85';
};
</script>

<template>
    <Head title="Sport Center" />

    <div class="min-h-screen bg-[#f7f8f6] text-slate-900">
        <header class="absolute inset-x-0 top-0 z-20 border-b border-white/10 bg-slate-950/20 text-white backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-2xl bg-white text-sm font-black text-slate-900">SC</div>
                    <div>
                        <p class="text-sm font-bold">Sport Center</p>
                        <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-white/60">Play. Move. Connect.</p>
                    </div>
                </Link>
                <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold text-white backdrop-blur-md">Booking Online</span>
            </div>
        </header>

        <main>
            <section class="relative flex min-h-[680px] items-center overflow-hidden bg-slate-950 text-white lg:min-h-[760px]">
                <img :src="heroImage" alt="Sport Center" class="absolute inset-0 h-full w-full object-cover" />
                <div class="absolute inset-0 bg-slate-950/65"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/55 to-slate-950/35"></div>

                <div class="relative z-10 mx-auto w-full max-w-7xl px-5 pt-24 sm:px-8 lg:pt-28">
                    <div class="max-w-4xl">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold backdrop-blur-md">
                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                            Booking lapangan online
                        </div>
                        <h1 class="text-5xl font-black leading-[0.95] tracking-[-0.05em] sm:text-7xl lg:text-8xl">
                            Main lebih.
                            <br />
                            <span class="text-emerald-400">Hidup lebih.</span>
                        </h1>
                        <p class="mt-7 max-w-2xl text-base leading-7 text-white/75 sm:text-xl sm:leading-8">
                            Temukan lapangan favoritmu, pilih jadwal yang tersedia, dan booking langsung tanpa ribet.
                        </p>
                        <a href="#sports" class="mt-9 inline-flex items-center gap-3 rounded-full bg-white px-6 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-400">
                            Lihat pilihan olahraga
                            <ArrowRight class="size-4" />
                        </a>
                    </div>
                </div>
            </section>

            <section id="sports" class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:py-16">
                <div class="mb-8 flex items-end justify-between gap-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Pilihan olahraga</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight">Pilih sport</h2>
                    </div>
                    <p class="hidden text-sm text-slate-400 sm:block">{{ zones.length }} olahraga tersedia</p>
                </div>

                <div v-if="zones.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="zone in zones"
                        :key="zone.id"
                        :href="`/booking/${zone.id}`"
                        class="group overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-2xl"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                            <img
                                :src="imageForSport(zone.name)"
                                :alt="`Lapangan ${zone.name}`"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                            <div class="absolute bottom-4 left-5 flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-md">
                                <MapPin class="size-3.5" />
                                Tersedia untuk booking
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-black">{{ zone.name }}</h3>
                                    <p class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                                        <Users class="size-4" />
                                        {{ zone.zone_spaces.length }} lapangan tersedia
                                    </p>
                                </div>
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-slate-900 text-white transition group-hover:translate-x-1">
                                    <ArrowRight class="size-5" />
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="rounded-3xl border border-slate-200 bg-white p-10 text-center">
                    <h2 class="text-xl font-black">Belum ada olahraga tersedia</h2>
                    <p class="mt-2 text-sm text-slate-400">Silakan coba lagi nanti.</p>
                </div>
            </section>
        </main>
    </div>
</template>
