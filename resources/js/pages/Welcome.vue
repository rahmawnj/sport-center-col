<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

type PricingRule = {
    price: number;
    day_type: string | null;
    start_time: string | null;
    end_time: string | null;
};

type PackageItem = {
    id: number;
    name: string;
    type: string;
    duration_minutes: number | null;
    duration_days: number | null;
    pricing_rules: PricingRule[];
};

type FacilityItem = {
    id: number;
    name: string;
    slug: string;
    available_courts: number;
    packages: PackageItem[];
};

type TrainerItem = {
    id: number;
    name: string;
    specialty: string | null;
};

type MembershipPackage = {
    id: number;
    name: string;
    price: number | string;
    duration_days: number | null;
    description: string | null;
};

const props = defineProps<{
    facilities: FacilityItem[];
    trainers: TrainerItem[];
    membershipPackages: MembershipPackage[];
    stats: {
        facilities: number;
        courts: number;
        trainers: number;
        membershipPackages: number;
    };
}>();

const currentYear = new Date().getFullYear();
const featuredFacilities = computed(() => props.facilities.slice(0, 3));

const formatCurrency = (amount: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(amount));

const lowestPrice = (facility: FacilityItem) => {
    const prices = facility.packages.flatMap((item) =>
        item.pricing_rules.map((rule) => Number(rule.price)),
    );
    return prices.length ? Math.min(...prices) : null;
};

const heroImages: Record<string, string> = {
    padel: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1100&q=85',
    billiard: 'https://images.unsplash.com/photo-1606925797300-0b35e9d1794e?auto=format&fit=crop&w=1100&q=85',
    gym: 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1100&q=85',
};

const facilityImage = (facility: FacilityItem) =>
    heroImages[facility.slug.toLowerCase()] ??
    'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=900&q=80';

const facilityDescription = (slug: string) => {
    const key = slug.toLowerCase();
    if (key.includes('padel')) return 'Rasakan permainan yang seru bersama teman dan tim.';
    if (key.includes('billiard')) return 'Nikmati waktu santai dengan permainan yang kompetitif.';
    if (key.includes('gym')) return 'Bangun rutinitas latihan dan capai target kebugaranmu.';
    return 'Temukan fasilitas olahraga yang sesuai dengan aktivitasmu.';
};
</script>

<template>
    <Head title="Sport Center — Olahraga, Lebih Mudah"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" /></Head>

    <div class="dream-home min-h-screen bg-white text-[#001428]">
        <header class="site-header sticky top-0 z-40 border-b border-slate-100 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 lg:px-8">
                <a href="/" class="brand flex items-center gap-2.5" aria-label="Sport Center beranda">
                    <span class="brand-mark"><i class="fa-solid fa-volleyball"></i></span>
                    <span class="text-xl font-extrabold tracking-tight">SPORT<span class="text-[#8dbb00]">CENTER</span></span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-semibold md:flex">
                    <a href="#olahraga" class="nav-link">Olahraga</a>
                    <a href="#pelatih" class="nav-link">Pelatih</a>
                    <a href="#membership" class="nav-link">Keanggotaan</a>
                </nav>

                <div class="flex items-center gap-2">
                    <Link href="/book" class="hidden rounded-full px-4 py-2.5 text-sm font-bold text-[#001428] transition hover:bg-slate-100 sm:inline-flex">Booking</Link>
                    <Link v-if="$page.props.auth.user" :href="dashboard()" class="btn-lime rounded-full px-5 py-2.5 text-sm font-extrabold">Dasbor</Link>
                    <template v-else>
                        <Link :href="login()" class="hidden rounded-full px-4 py-2.5 text-sm font-bold sm:inline-flex">Masuk</Link>
                        <Link :href="register()" class="btn-lime rounded-full px-5 py-2.5 text-sm font-extrabold">Daftar</Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="hero-section relative overflow-hidden">
                <div class="hero-glow"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 md:py-20 lg:grid-cols-[1.02fr_.98fr] lg:px-8 lg:py-24">
                    <div class="relative z-10">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-[#b6e52b]">
                            <span class="h-2 w-2 rounded-full bg-[#a4da01]"></span>
                            Waktunya bergerak
                        </div>
                        <h1 class="max-w-2xl text-4xl font-black leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-7xl">
                            Olahraga favoritmu.<br />
                            <span class="text-[#a4da01]">Jadwalmu.</span><br />
                            Semudah itu.
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                            Temukan lapangan, pilih jadwal, dan mulai bermain. Semua kebutuhan olahragamu dalam satu tempat.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link href="/book" class="btn-lime inline-flex items-center gap-3 rounded-full px-7 py-4 font-extrabold">
                                Pesan Lapangan <i class="fa-solid fa-arrow-right"></i>
                            </Link>
                            <a href="#olahraga" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-7 py-4 font-bold text-white transition hover:bg-white/10">
                                Jelajahi fasilitas <i class="fa-solid fa-arrow-down"></i>
                            </a>
                        </div>
                        <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm text-slate-300">
                            <span><i class="fa-solid fa-circle-check mr-2 text-[#a4da01]"></i>Pemesanan praktis</span>
                            <span><i class="fa-solid fa-circle-check mr-2 text-[#a4da01]"></i>Tarif transparan</span>
                        </div>
                    </div>

                    <div class="hero-visual relative mx-auto w-full max-w-xl">
                        <div class="hero-image-main"></div>
                        <div class="hero-image-small"></div>
                        <div class="hero-floating-card">
                            <span class="mb-1 block text-xs font-semibold text-slate-500">Pilihan aktivitas</span>
                            <span class="text-lg font-extrabold text-[#001428]">{{ stats.facilities }} fasilitas olahraga</span>
                            <span class="mt-2 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-[#a4da01]"></span>Siap untuk jadwal berikutnya</span>
                        </div>
                        <div class="hero-circle-label"><i class="fa-solid fa-bolt"></i><span>LET'S<br />PLAY</span></div>
                    </div>
                </div>
                <div class="hero-bottom-line"></div>
            </section>

            <section class="relative z-10 mx-auto -mt-1 max-w-7xl px-5 lg:px-8">
                <div class="stats-panel grid grid-cols-2 gap-5 rounded-2xl border border-slate-100 bg-white p-6 shadow-xl shadow-slate-900/5 md:grid-cols-4 md:px-10 md:py-7">
                    <div class="stat-item"><span class="stat-number">{{ stats.facilities }}</span><span class="stat-label">Jenis fasilitas</span></div>
                    <div class="stat-item"><span class="stat-number">{{ stats.courts }}</span><span class="stat-label">Lapangan tersedia</span></div>
                    <div class="stat-item"><span class="stat-number">{{ stats.trainers }}</span><span class="stat-label">Pelatih terdaftar</span></div>
                    <div class="stat-item"><span class="stat-number">{{ stats.membershipPackages }}</span><span class="stat-label">Paket keanggotaan</span></div>
                </div>
            </section>

            <section id="olahraga" class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
                <div class="section-heading mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="section-kicker">Pilih permainanmu</p>
                        <h2 class="section-title">Temukan <span>olahraga favoritmu</span></h2>
                        <p class="mt-3 max-w-xl text-slate-500">Pilih fasilitas yang kamu suka, cek tarifnya, lalu pesan waktu bermainmu.</p>
                    </div>
                    <Link href="/book" class="inline-flex items-center gap-2 self-start text-sm font-extrabold text-[#001428] hover:text-[#729700] sm:self-auto">Lihat semua fasilitas <i class="fa-solid fa-arrow-right"></i></Link>
                </div>

                <div v-if="featuredFacilities.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="(facility, index) in featuredFacilities" :key="facility.id" class="sport-card group overflow-hidden rounded-2xl border border-slate-100 bg-white">
                        <div class="sport-card-image relative">
                            <img :src="facilityImage(facility)" :alt="facility.name" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                            <span class="absolute left-4 top-4 rounded-full bg-[#a4da01] px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide text-[#001428]">0{{ index + 1 }} · Pilihan olahraga</span>
                            <span class="absolute bottom-4 right-4 rounded-full bg-[#001428]/90 px-3 py-2 text-xs font-bold text-white"><i class="fa-solid fa-location-dot mr-1.5 text-[#a4da01]"></i>{{ facility.available_courts }} tersedia</span>
                        </div>
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-extrabold">{{ facility.name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ facilityDescription(facility.slug) }}</p>
                                </div>
                                <span class="sport-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                            </div>
                            <div class="mt-6 flex items-end justify-between border-t border-slate-100 pt-5">
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">Mulai dari</p>
                                    <p class="mt-1 text-lg font-black text-[#001428]">{{ lowestPrice(facility) !== null ? formatCurrency(lowestPrice(facility) ?? 0) : 'Cek tarif' }}<span v-if="lowestPrice(facility) !== null" class="text-xs font-semibold text-slate-400"> / paket</span></p>
                                </div>
                                <Link :href="`/book?facility=${encodeURIComponent(facility.slug)}`" class="btn-lime inline-flex items-center gap-2 rounded-full px-4 py-3 text-sm font-extrabold">Pesan <i class="fa-solid fa-arrow-right"></i></Link>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">Fasilitas olahraga belum tersedia. Silakan cek kembali nanti.</div>
            </section>

            <section v-if="trainers.length" id="pelatih" class="bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                    <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="section-kicker">Berlatih bersama ahlinya</p>
                            <h2 class="section-title">Kenalan dengan <span>pelatih kami</span></h2>
                            <p class="mt-3 max-w-xl text-slate-500">Temukan dukungan latihan dari pelatih yang terdaftar di pusat olahraga kami.</p>
                        </div>
                        <Link href="/book" class="inline-flex items-center gap-2 self-start text-sm font-extrabold sm:self-auto">Tanya jadwal <i class="fa-solid fa-arrow-right"></i></Link>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <article v-for="(trainer, index) in trainers" :key="trainer.id" class="trainer-card rounded-2xl bg-white p-5">
                            <div class="trainer-avatar" :class="'trainer-avatar-' + (index % 4)">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <p class="mt-5 text-xs font-extrabold uppercase tracking-[.15em] text-[#7a9d00]">Pelatih</p>
                            <h3 class="mt-2 text-lg font-extrabold">{{ trainer.name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ trainer.specialty || 'Pelatih olahraga' }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section v-if="membershipPackages.length" id="membership" class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
                <div class="membership-banner relative overflow-hidden rounded-3xl px-7 py-10 sm:px-12 sm:py-14 lg:px-16">
                    <div class="membership-decoration"></div>
                    <div class="relative z-10 grid gap-10 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                        <div>
                            <p class="section-kicker text-[#b6e52b]">Lebih rutin, lebih maksimal</p>
                            <h2 class="mt-3 text-3xl font-black leading-tight text-white sm:text-4xl">Jadikan olahraga bagian dari harimu.</h2>
                            <p class="mt-4 max-w-md leading-7 text-slate-300">Lihat pilihan paket keanggotaan yang tersedia dan temukan yang cocok untuk rutinitasmu.</p>
                            <Link href="/book" class="btn-lime mt-7 inline-flex items-center gap-3 rounded-full px-6 py-3.5 font-extrabold">Lihat pilihan paket <i class="fa-solid fa-arrow-right"></i></Link>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <article v-for="(membership, index) in membershipPackages" :key="membership.id" class="membership-card rounded-2xl p-5" :class="index === 0 ? 'membership-card-featured' : ''">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-extrabold">{{ membership.name }}</h3>
                                    <span v-if="index === 0" class="rounded-full bg-[#a4da01] px-2.5 py-1 text-[10px] font-black uppercase text-[#001428]">Pilihan</span>
                                </div>
                                <p class="mt-5 text-2xl font-black">{{ formatCurrency(membership.price) }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ membership.duration_days ? membership.duration_days + ' hari' : 'Durasi sesuai ketentuan' }}</p>
                                <p v-if="membership.description" class="mt-4 text-sm leading-6 text-slate-300">{{ membership.description }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pb-20 lg:px-8 lg:pb-28">
                <div class="cta-strip mx-auto flex max-w-7xl flex-col gap-6 rounded-3xl px-7 py-9 sm:flex-row sm:items-center sm:justify-between sm:px-12">
                    <div>
                        <p class="section-kicker">Mulai dari sekarang</p>
                        <h2 class="mt-2 text-2xl font-black sm:text-3xl">Siap untuk pertandingan berikutnya?</h2>
                        <p class="mt-2 text-sm text-slate-500">Pilih fasilitas dan amankan jadwal bermainmu.</p>
                    </div>
                    <Link href="/book" class="btn-lime inline-flex shrink-0 items-center justify-center gap-3 rounded-full px-7 py-4 font-extrabold">Booking sekarang <i class="fa-solid fa-arrow-right"></i></Link>
                </div>
            </section>
        </main>

        <footer class="bg-[#001428] text-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-9 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="brand-mark"><i class="fa-solid fa-volleyball"></i></span>
                    <span class="text-lg font-extrabold">SPORT<span class="text-[#a4da01]">CENTER</span></span>
                </a>
                <p class="text-sm text-slate-400">Temukan permainanmu. Bergerak lebih aktif.</p>
                <p class="text-xs text-slate-500">© {{ currentYear }} Sport Center. Hak cipta dilindungi.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.dream-home { --lime: #a4da01; --navy: #001428; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
.btn-lime { background: var(--lime); color: var(--navy); transition: transform .2s ease, background .2s ease; }
.btn-lime:hover { background: #b8ec24; transform: translateY(-2px); }
.brand-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; background: var(--lime); color: var(--navy); font-size: 18px; }
.nav-link { position: relative; padding: 10px 0; transition: color .2s ease; }
.nav-link:hover { color: #729700; }
.nav-link::after { position: absolute; right: 0; bottom: 2px; left: 0; height: 2px; background: var(--lime); content: ''; transform: scaleX(0); transform-origin: left; transition: transform .2s ease; }
.nav-link:hover::after { transform: scaleX(1); }
.hero-section { background: var(--navy); }
.hero-glow { position: absolute; top: -240px; right: -130px; width: 660px; height: 660px; border: 1px solid rgba(164,218,1,.16); border-radius: 50%; box-shadow: 0 0 0 65px rgba(164,218,1,.035), 0 0 0 130px rgba(164,218,1,.025); }
.hero-visual { min-height: 440px; }
.hero-image-main { position: absolute; top: 0; right: 2%; width: 78%; height: 390px; border: 8px solid rgba(255,255,255,.08); border-radius: 180px 180px 28px 28px; background: linear-gradient(180deg, rgba(0,20,40,.05), rgba(0,20,40,.25)), url('https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1100&q=85') center/cover; box-shadow: 0 28px 60px rgba(0,0,0,.22); }
.hero-image-small { position: absolute; bottom: 4px; left: 0; width: 42%; height: 185px; border: 7px solid var(--navy); border-radius: 24px; background: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=700&q=85') center/cover; }
.hero-floating-card { position: absolute; right: 0; bottom: 34px; display: flex; flex-direction: column; min-width: 220px; border-radius: 18px; background: white; padding: 18px 22px; box-shadow: 0 18px 45px rgba(0,0,0,.2); }
.hero-circle-label { position: absolute; top: 34px; left: 0; display: flex; width: 84px; height: 84px; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border-radius: 50%; background: var(--lime); color: var(--navy); font-size: 10px; font-weight: 900; letter-spacing: .12em; transform: rotate(-12deg); }
.hero-circle-label i { font-size: 22px; }
.hero-bottom-line { position: absolute; right: 0; bottom: 0; left: 0; height: 5px; background: linear-gradient(90deg, transparent, var(--lime), transparent); opacity: .8; }
.stat-item { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; }
.stat-number { font-size: 30px; font-weight: 900; letter-spacing: -.04em; }
.stat-label { color: #64748b; font-size: 12px; font-weight: 600; }
.section-kicker { color: #789900; font-size: 11px; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; }
.section-title { margin-top: 10px; color: var(--navy); font-size: clamp(28px, 3vw, 40px); font-weight: 900; line-height: 1.16; letter-spacing: -.04em; }
.section-title span { color: #7da500; }
.sport-card { box-shadow: 0 12px 35px rgba(0,20,40,.035); transition: transform .25s ease, box-shadow .25s ease; }
.sport-card:hover { transform: translateY(-6px); box-shadow: 0 24px 50px rgba(0,20,40,.11); }
.sport-card-image { height: 245px; overflow: hidden; background: #e9eee1; }
.sport-icon { display: grid; width: 38px; height: 38px; flex-shrink: 0; place-items: center; border-radius: 50%; background: #f2f7df; color: #6e9000; }
.trainer-card { border: 1px solid #e9eee1; transition: transform .2s ease, box-shadow .2s ease; }
.trainer-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,20,40,.07); }
.trainer-avatar { display: grid; width: 100%; height: 135px; place-items: center; border-radius: 14px; color: var(--navy); font-size: 38px; }
.trainer-avatar-0 { background: linear-gradient(135deg, #dceca5, #f4f7e8); }
.trainer-avatar-1 { background: linear-gradient(135deg, #d8e9ef, #f2f7f8); }
.trainer-avatar-2 { background: linear-gradient(135deg, #f3dfc9, #fbf4eb); }
.trainer-avatar-3 { background: linear-gradient(135deg, #ded8f3, #f5f2fc); }
.membership-banner { background: var(--navy); }
.membership-decoration { position: absolute; top: -170px; right: -110px; width: 500px; height: 500px; border: 1px solid rgba(164,218,1,.16); border-radius: 50%; box-shadow: 0 0 0 50px rgba(164,218,1,.035), 0 0 0 100px rgba(164,218,1,.025); }
.membership-card { border: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.06); color: white; backdrop-filter: blur(8px); }
.membership-card-featured { border-color: rgba(164,218,1,.65); background: rgba(164,218,1,.09); }
.cta-strip { border: 1px solid #e7edd7; background: #f7faef; }
@media (max-width: 640px) {
    .hero-visual { min-height: 350px; }
    .hero-image-main { height: 305px; width: 82%; }
    .hero-image-small { height: 140px; width: 43%; }
    .hero-floating-card { right: 0; bottom: 16px; min-width: 185px; padding: 13px 15px; }
    .hero-floating-card .text-lg { font-size: 15px; }
    .hero-circle-label { top: 15px; width: 68px; height: 68px; }
    .stat-number { font-size: 25px; }
}
</style>
