<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { dashboard, login, register } from '@/routes';

type Space = {
    id: number;
    name: string;
    capacity: number;
    status: string;
    facilities: string[];
    starting_price: number | string | null;
};

type Zone = {
    id: number;
    name: string;
    pricing_model: string;
    is_online_bookable: boolean;
    spaces: Space[];
    starting_price: number | string | null;
};

type Trainer = { id: number; name: string; specialty: string };
type AddOn = { id: number; name: string; price: number | string; stock: number };

const props = defineProps<{
    zones: Zone[];
    trainers: Trainer[];
    addOns: AddOn[];
}>();

const currentYear = new Date().getFullYear();
const totalSpaces = props.zones.reduce((total, zone) => total + zone.spaces.length, 0);
const animatedZones = ref(0);
const animatedSpaces = ref(0);
const animatedTrainers = ref(0);
let statsTimer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    const duration = 1600;
    const startedAt = Date.now();
    const targets = [props.zones.length, totalSpaces, props.trainers.length];

    statsTimer = setInterval(() => {
        const progress = Math.min((Date.now() - startedAt) / duration, 1);
        const easedProgress = 1 - Math.pow(1 - progress, 3);

        animatedZones.value = Math.floor(targets[0] * easedProgress);
        animatedSpaces.value = Math.floor(targets[1] * easedProgress);
        animatedTrainers.value = Math.floor(targets[2] * easedProgress);

        if (progress >= 1) {
            animatedZones.value = targets[0];
            animatedSpaces.value = targets[1];
            animatedTrainers.value = targets[2];
            if (statsTimer) clearInterval(statsTimer);
        }
    }, 16);
});

onBeforeUnmount(() => {
    if (statsTimer) clearInterval(statsTimer);
});

const zoneImages: Record<string, string> = {
    padel: 'photo-1626224583764-f87db24ac4ea',
    billiard: 'photo-1609710228159-0fa9bd7c0bcb',
    gym: 'photo-1534438327276-14e5300c3a48',
    yoga: 'photo-1506126613408-eca07ce68773',
    pilates: 'photo-1518611012118-696072aa579a',
    'ice skating': 'photo-1517466787929-bc90951d0974',
    spinning: 'photo-1576678927484-cc907957088c',
};

function imageForZone(name: string, index: number) {
    const key = name.toLowerCase();
    const image = Object.entries(zoneImages).find(([label]) => key.includes(label))?.[1];
    const fallback = [
        'photo-1531415074968-036ba1b575da',
        'photo-1540747913346-19e32dc3e97e',
        'photo-1574629810360-7efbbe195018',
    ][index % 3];
    const imageId = image && !image.includes(' ') ? image : fallback;

    return `https://images.unsplash.com/${imageId}?auto=format&fit=crop&w=900&q=85`;
}

function formatPrice(value: number | string | null | undefined) {
    if (value === null || value === undefined || value === '') return null;

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function pricingLabel(model: string) {
    const labels: Record<string, string> = {
        per_person: 'Per orang',
        per_space: 'Per ruang',
        per_trainer_session: 'Sesi pelatih',
        per_table: 'Per meja',
    };

    return labels[model] ?? 'Tarif fleksibel';
}
</script>

<template>
    <Head>
        <title>Sport Center | Sewa Lapangan & Booking Fasilitas Olahraga</title>
        <meta head-key="description" name="description" content="Temukan dan booking fasilitas olahraga dengan mudah. Jelajahi zona, pilih ruang, cek tarif transparan, lihat jadwal tersedia, dan pesan tanpa harus menjadi member." />
        <meta head-key="robots" name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <meta head-key="keywords" name="keywords" content="tempat olahraga, fasilitas olahraga, sewa lapangan, booking lapangan, booking olahraga, sewa ruang olahraga, harga sewa lapangan, jadwal olahraga" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:site_name" property="og:site_name" content="Sport Center" />
        <meta head-key="og:title" property="og:title" content="Sport Center | Sewa Lapangan & Booking Fasilitas Olahraga" />
        <meta head-key="og:description" property="og:description" content="Cari fasilitas olahraga, bandingkan tarif, cek jadwal, dan booking dengan mudah tanpa daftar member." />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" content="Sport Center | Booking Fasilitas Olahraga" />
        <meta head-key="twitter:description" name="twitter:description" content="Jelajahi zona olahraga, cek harga dan jadwal, lalu booking dengan mudah." />
    </Head>

    <div class="min-h-screen overflow-hidden bg-white text-[#15251f]">
        <header class="sticky top-0 z-50 border-b border-black/5 bg-[#f6f7f2]/95 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
                <a href="/" class="flex items-center gap-3" aria-label="Sport Center home">
                    <img src="/logo.png" alt="Sport Center" class="h-11 w-11 rounded-xl object-contain" />
                    <span>
                        <span class="block text-lg font-black tracking-tight">sport<span class="text-[#6b8e23]">center.</span></span>
                        <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500">Move your way</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-600 md:flex">
                    <a href="#zones" class="transition hover:text-[#15251f]">Olahraga</a>
                    <a href="#coaches" class="transition hover:text-[#15251f]">Pelatih</a>
                    <a href="#extras" class="transition hover:text-[#15251f]">Layanan tambahan</a>
                </nav>

                <div class="flex items-center gap-2">
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="hidden rounded-full px-4 py-2.5 text-sm font-bold sm:inline-flex">Dasbor</Link>
                    <template v-else>
                        <Link :href="login()" class="hidden rounded-full px-4 py-2.5 text-sm font-bold sm:inline-flex">Masuk</Link>
                    </template>
                    <Link href="#zones" class="rounded-full bg-[#172720] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#2d4436] sm:px-5">
                        Cari fasilitas <span class="ml-1">↗</span>
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <section class="bg-[#f3f6ec]">
                <div class="mx-auto grid max-w-7xl items-center gap-10 px-5 pb-16 pt-10 sm:px-8 sm:pb-20 sm:pt-16 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14 lg:pt-20">
                <div class="relative z-10">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#dfe4d5] bg-white px-3 py-2 text-xs font-bold uppercase tracking-[0.15em] text-[#60734a]">
                        <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                        Your space to play
                    </div>
                    <h1 class="max-w-2xl text-5xl font-black leading-[0.98] tracking-[-0.055em] sm:text-6xl lg:text-[4.6rem]">
                        Tempat olahraga<br />
                        <span class="text-[#8ba83d]">buat caramu bergerak.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">
                        Cari tempat olahraga untuk latihan atau bermain bersama teman. Bandingkan fasilitas, lihat harga sewa, cek jadwal yang tersedia, dan booking ruang olahraga secara online tanpa perlu daftar sebagai member.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#zones" class="inline-flex items-center gap-3 rounded-full bg-[#172720] px-6 py-4 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#2d4436]">
                            Jelajahi fasilitas <span class="text-lg">↗</span>
                        </a>
                        <Link v-if="!$page.props.auth.user" :href="register()" class="rounded-full border border-[#d7ddcf] px-6 py-4 text-sm font-bold transition hover:bg-white">
                            Buat akun
                        </Link>
                    </div>

                    <div class="mt-10 grid max-w-lg grid-cols-3 border-t border-[#dce1d5] pt-6">
                        <div>
                            <p class="text-3xl font-black tracking-tight">{{ animatedZones }}<span class="text-[#8ba83d]">+</span></p>
                            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">Zona olahraga</p>
                        </div>
                        <div class="border-l border-[#dce1d5] pl-5">
                            <p class="text-3xl font-black tracking-tight">{{ animatedSpaces }}<span class="text-[#8ba83d]">+</span></p>
                            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">Ruang tersedia</p>
                        </div>
                        <div class="border-l border-[#dce1d5] pl-5">
                            <p class="text-3xl font-black tracking-tight">{{ animatedTrainers }}<span class="text-[#8ba83d]">+</span></p>
                            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">Pelatih</p>
                        </div>
                    </div>
                </div>

                <div class="relative min-h-[390px] sm:min-h-[500px]">
                    <div class="absolute -right-8 -top-6 h-40 w-40 rounded-full bg-[#e1f1b0] blur-2xl sm:h-56 sm:w-56"></div>
                    <div class="absolute bottom-0 left-0 right-5 top-0 overflow-hidden rounded-[2rem] bg-[#dce4d0] sm:left-8 sm:rounded-[2.5rem]">
                        <img
                            src="https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=1400&q=90"
                            alt="Lapangan olahraga dengan suasana aktif"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#101d17]/75 via-transparent to-black/5"></div>
                        <div class="absolute bottom-6 left-6 right-6 text-white sm:bottom-8 sm:left-8">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#d8ff62]">Play more, worry less</p>
                            <p class="mt-2 max-w-sm text-2xl font-black leading-tight sm:text-3xl">Waktunya fokus ke permainanmu.</p>
                        </div>
                    </div>
                    <div class="absolute right-0 top-8 max-w-[190px] rounded-2xl border border-white/70 bg-white/95 p-4 shadow-xl backdrop-blur sm:right-[-8px] sm:top-12 sm:max-w-[220px] sm:p-5">
                        <div class="flex items-center gap-2">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#eaf5cd] text-lg">✓</span>
                            <span class="text-xs font-bold text-slate-500">PILIHANMU</span>
                        </div>
                        <p class="mt-3 text-sm font-extrabold">Satu tempat, banyak cara bergerak.</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Zona, ruang, pelatih, dan add-on dalam satu platform.</p>
                    </div>
                </div>
                </div>
            </section>

            <section class="overflow-hidden border-y border-[#e3e7dc] bg-white py-5" aria-label="Keunggulan Sport Center">
                <div class="sport-benefits-marquee flex w-max items-center">
                    <div v-for="copy in 2" :key="copy" class="flex shrink-0 items-center gap-8 px-4 sm:gap-12 sm:px-6" :aria-hidden="copy === 2 ? 'true' : undefined">
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eff5e1] text-lg">⌖</span><span class="whitespace-nowrap text-sm font-bold sm:text-base">Zona olahraga</span></div>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#8ba83d]"></span>
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eff5e1] text-lg">◷</span><span class="whitespace-nowrap text-sm font-bold sm:text-base">Tarif transparan</span></div>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#8ba83d]"></span>
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eff5e1] text-lg">♧</span><span class="whitespace-nowrap text-sm font-bold sm:text-base">Pelatih profesional</span></div>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#8ba83d]"></span>
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-[#eff5e1] text-lg">＋</span><span class="whitespace-nowrap text-sm font-bold sm:text-base">Add-on praktis</span></div>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#8ba83d]"></span>
                    </div>
                </div>
            </section>

            <section id="zones" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div class="mb-8 flex flex-col justify-between gap-4 sm:mb-10 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-[#819b42]">Explore the spaces</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Cari fasilitas olahraga favoritmu.</h2>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">Jelajahi pilihan fasilitas olahraga, lihat ruang yang tersedia, cek fasilitas pendukung, dan bandingkan harga sebelum melakukan booking.</p>
                    </div>
                    <Link href="#zones" class="inline-flex w-fit items-center gap-2 text-sm font-extrabold">Jelajahi fasilitas <span>↗</span></Link>
                </div>

                <div v-if="props.zones.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="(zone, index) in props.zones" :key="zone.id" :href="`/zones/${zone.id}`" class="group block overflow-hidden rounded-[1.6rem] border border-[#e4e8df] bg-white transition duration-300 hover:-translate-y-1 hover:border-[#c6d99b] hover:shadow-xl hover:shadow-[#26351c]/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8ba83d] focus-visible:ring-offset-4">
                        <div class="relative h-52 overflow-hidden bg-[#e8ecdf]">
                            <img :src="imageForZone(zone.name, index)" :alt="zone.name" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/45 to-transparent"></div>
                            <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wide text-[#24352a]">{{ pricingLabel(zone.pricing_model) }}</span>
                            <span class="absolute bottom-4 left-4 text-2xl font-black text-white">{{ zone.name }}</span>
                            <span v-if="zone.is_online_bookable" class="absolute bottom-4 right-4 rounded-full bg-[#d8ff62] px-3 py-1.5 text-[10px] font-black uppercase tracking-wide text-[#26351c]">Online</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm text-slate-500">{{ zone.spaces.length }} ruang terdaftar</p>
                                <p v-if="formatPrice(zone.starting_price)" class="text-sm font-black">{{ formatPrice(zone.starting_price) }}<span class="font-medium text-slate-400"> / mulai</span></p>
                                <p v-else class="text-sm font-semibold text-slate-400">Tarif menyusul</p>
                            </div>
                            <div v-if="zone.spaces.length" class="mt-4 flex flex-wrap gap-2">
                                <span v-for="space in zone.spaces.slice(0, 3)" :key="space.id" class="rounded-lg bg-[#f3f5ef] px-2.5 py-1.5 text-xs font-semibold text-slate-600">{{ space.name }}</span>
                                <span v-if="zone.spaces.length > 3" class="rounded-lg bg-[#f3f5ef] px-2.5 py-1.5 text-xs font-semibold text-slate-500">+{{ zone.spaces.length - 3 }} lainnya</span>
                            </div>
                            <div class="mt-5 flex items-center justify-between border-t border-[#edf0e9] pt-4">
                                <span class="text-xs font-semibold text-slate-500">{{ zone.spaces.filter((space) => space.status === 'available').length }} ruang tersedia</span>
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-[#172720] text-lg text-white transition group-hover:bg-[#d8ff62] group-hover:text-[#172720]" aria-hidden="true">↗</span>
                            </div>
                        </div>
                    </Link>
                </div>
                <div v-else class="rounded-3xl border border-dashed border-[#cbd3c1] bg-white p-10 text-center">
                    <p class="text-lg font-bold">Zona olahraga belum tersedia</p>
                    <p class="mt-2 text-sm text-slate-500">Zona yang ditambahkan oleh pengelola akan tampil di bagian ini.</p>
                </div>
            </section>

            <section id="coaches" class="bg-[#234832] text-white">
                <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-[#d8ff62]">Better with guidance</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Latihan bareng pelatih.</h2>
                        <p class="mt-4 max-w-md text-sm leading-7 text-white/75 sm:text-base">Kenali tim pelatih yang terdaftar dan temukan spesialisasi yang sesuai dengan target latihanmu.</p>
                        <Link href="/trainers" class="mt-7 inline-flex items-center gap-3 rounded-full bg-[#172720] px-5 py-3.5 text-sm font-bold text-white transition hover:bg-[#2d4436]">Kenali pelatih <span>↗</span></Link>
                    </div>
                    <div v-if="props.trainers.length" class="grid gap-3 sm:grid-cols-2">
                        <article v-for="(trainer, index) in props.trainers.slice(0, 4)" :key="trainer.id" class="flex items-center gap-4 rounded-2xl border border-white/80 bg-white p-4 shadow-sm">
                            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-xl font-black" :class="index % 2 === 0 ? 'bg-[#d8ff62] text-[#26351c]' : 'bg-[#e7ebdf] text-[#60734a]'">{{ trainer.name.split(' ').map((part) => part[0]).slice(0, 2).join('') }}</div>
                            <div class="min-w-0">
                                <p class="truncate font-extrabold">{{ trainer.name }}</p>
                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ trainer.specialty }}</p>
                            </div>
                        </article>
                    </div>
                    <div v-else class="rounded-3xl border border-white bg-white p-8 text-sm text-slate-500">Profil pelatih akan tampil di sini setelah ditambahkan oleh pengelola.</div>
                </div>
            </section>

            <section id="extras" class="bg-[#f3f6ec] px-5 py-16 sm:px-8 sm:py-20">
                <div class="mx-auto max-w-7xl">
                <div class="mb-8">
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-[#819b42]">The little extras</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Lengkapi sesi olahragamu.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-base">Add-on yang tersedia ditampilkan berdasarkan stok yang tercatat.</p>
                </div>
                <div v-if="props.addOns.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="addon in props.addOns" :key="addon.id" class="flex items-center justify-between gap-4 rounded-2xl border border-[#e3e7dc] bg-white p-5 transition hover:border-[#bdce8f]">
                        <div class="flex items-center gap-4">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-[#eff5e1] text-xl">＋</span>
                            <div>
                                <h3 class="font-extrabold">{{ addon.name }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Stok tersedia: {{ addon.stock }}</p>
                            </div>
                        </div>
                        <p class="shrink-0 text-sm font-black">{{ formatPrice(addon.price) }}</p>
                    </article>
                </div>
                <div v-else class="rounded-2xl bg-white p-6 text-sm text-slate-500">Belum ada layanan tambahan yang tersedia saat ini.</div>
                </div>
            </section>

            <section class="px-5 pb-16 sm:px-8 sm:pb-20">
                <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-[#172720] px-6 py-10 text-white sm:px-10 sm:py-14 lg:px-16">
                    <div class="absolute -right-10 -top-24 h-72 w-72 rounded-full bg-[#d8ff62]/15 blur-2xl"></div>
                    <div class="relative flex flex-col justify-between gap-8 md:flex-row md:items-center">
                        <div class="max-w-2xl">
                            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#d8ff62]">Your next session starts here</p>
                            <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Siap bergerak hari ini?</h2>
                            <p class="mt-3 text-sm leading-7 text-white/65 sm:text-base">Jelajahi zona, cek ruang, dan rencanakan waktu olahraga berikutnya.</p>
                        </div>
                        <a href="#zones" class="inline-flex w-fit shrink-0 items-center gap-4 rounded-full bg-[#d8ff62] px-6 py-4 text-sm font-black text-[#172720] transition hover:bg-white">Temukan ruang <span class="text-lg">↗</span></a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-[#e0e5d9] bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <a href="/" class="flex items-center gap-2 font-black text-[#172720]"><img src="/logo.png" alt="Sport Center" class="h-8 w-8 rounded-lg object-contain" /> sportcenter.</a>
                <p>© {{ currentYear }} Sport Center. Move your way.</p>
                <div class="flex gap-5 font-semibold"><a href="#zones" class="hover:text-[#172720]">Fasilitas</a><a href="#coaches" class="hover:text-[#172720]">Pelatih</a><a href="#extras" class="hover:text-[#172720]">Add-on</a></div>
            </div>
        </footer>
    </div>
</template>

<style>
.sport-benefits-marquee {
    animation: sport-benefits-scroll 28s linear infinite;
    will-change: transform;
}
.sport-benefits-marquee:hover {
    animation-play-state: paused;
}
@keyframes sport-benefits-scroll {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}
@media (prefers-reduced-motion: reduce) {
    .sport-benefits-marquee {
        animation: none;
        width: 100%;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
    }
}
</style>
