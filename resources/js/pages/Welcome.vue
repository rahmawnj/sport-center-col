<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

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

type TrainerPackageItem = {
    id: number;
    facility_id: number;
    name: string;
    total_sessions: number;
    duration_days: number;
    price: number;
    description: string | null;
    sports: string[];
    trainers: TrainerItem[];
};


type ClassScheduleItem = {
    id: number;
    day: string;
    start_time: string;
    end_time: string;
    trainer: string | null;
    capacity: number;
    price: number;
};

type ClassItem = {
    id: number;
    name: string;
    description: string | null;
    facility: string | null;
    duration_minutes: number;
    schedules: ClassScheduleItem[];
};

type MembershipPackage = {
    id: number;
    facility_id: number;
    name: string;
    facility: string | null;
    price: number | null;
    duration_days: number;
    description: string;
};

const props = defineProps<{
    facilities: FacilityItem[];
    classes: ClassItem[];
    trainers: TrainerItem[];
    membershipPackages: MembershipPackage[];
    trainerPackages: TrainerPackageItem[];
    stats: {
        facilities: number;
        courts: number;
        trainers: number;
        membershipPackages: number;
    };
}>();

const featuredFacilities = computed(() => props.facilities);
const trainerPackageSports = computed(() =>
    Array.from(new Set(props.trainerPackages.flatMap((item) => item.sports).filter(Boolean))).sort((a, b) =>
        a.localeCompare(b, 'id'),
    ),
);
const selectedTrainerSport = ref('Semua');
const visibleTrainerPackages = computed(() =>
    selectedTrainerSport.value === 'Semua'
        ? props.trainerPackages
        : props.trainerPackages.filter((item) => item.sports.includes(selectedTrainerSport.value)),
);

const selectedMembershipPackage = ref<MembershipPackage | null>(null);
const selectedMembershipTrainerPackage = ref<TrainerPackageItem | null>(null);
const selectedMembershipTrainer = ref<TrainerItem | null>(null);
const membershipModalOpen = ref(false);
const membershipPaymentMethod = ref<'bank_transfer' | 'cash'>('bank_transfer');

const openMembershipCheckout = (item: MembershipPackage) => {
    selectedMembershipPackage.value = item;
    selectedMembershipTrainerPackage.value = null;
    selectedMembershipTrainer.value = null;
    membershipPaymentMethod.value = 'bank_transfer';
    membershipModalOpen.value = true;
};
const closeMembershipCheckout = () => {
    membershipModalOpen.value = false;
};
const continueMembershipCheckout = () => {
    if (!selectedMembershipPackage.value) {
        return;
    }

    if (selectedMembershipTrainerPackage.value && !selectedMembershipTrainer.value) {
        return;
}

    const params = new URLSearchParams({
        package_id: String(selectedMembershipPackage.value.id),
        payment_method: membershipPaymentMethod.value,
    });

    if (selectedMembershipTrainerPackage.value) {
        params.set('trainer_package_id', String(selectedMembershipTrainerPackage.value.id));
        params.set('trainer_id', String(selectedMembershipTrainer.value!.id));
    }

    router.get(`/membership/register?${params.toString()}`);
};

const selectedTrainerPackage = ref<TrainerPackageItem | null>(null);
const selectedTrainer = ref<TrainerItem | null>(null);
const trainerModalOpen = ref(false);
const trainerPaymentMethod = ref<'bank_transfer' | 'cash'>('bank_transfer');
const openTrainerCheckout = (item: TrainerPackageItem) => {
    selectedTrainerPackage.value = item;
    selectedTrainer.value = null;
    trainerPaymentMethod.value = 'bank_transfer';
    trainerModalOpen.value = true;
};
const closeTrainerCheckout = () => {
    trainerModalOpen.value = false;
};
const continueTrainerCheckout = () => {
    if (!selectedTrainerPackage.value || !selectedTrainer.value) {
        return;
    }

    router.get('/membership/register', {
        purchase_type: 'trainer',
        trainer_package_id: selectedTrainerPackage.value.id,
        trainer_id: selectedTrainer.value.id,
        payment_method: trainerPaymentMethod.value,
    });
};


const statsPanel = ref<HTMLElement | null>(null);
const animatedStats = ref({
    facilities: 0,
    courts: 0,
    trainers: 0,
    membershipPackages: 0,
});
let statsObserver: IntersectionObserver | null = null;
let animationFrame = 0;

const animateStats = () => {
    cancelAnimationFrame(animationFrame);
    const targets = {
        facilities: Number(props.stats.facilities) || 0,
        courts: Number(props.stats.courts) || 0,
        trainers: Number(props.stats.trainers) || 0,
        membershipPackages: Number(props.stats.membershipPackages) || 0,
    };

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        animatedStats.value = targets;

        return;
    }

    const duration = 1400;
    const startedAt = performance.now();
    const tick = (now: number) => {
        const progress = Math.min((now - startedAt) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 4);
        animatedStats.value = {
            facilities: Math.round(targets.facilities * eased),
            courts: Math.round(targets.courts * eased),
            trainers: Math.round(targets.trainers * eased),
            membershipPackages: Math.round(targets.membershipPackages * eased),
        };

        if (progress < 1) {
animationFrame = requestAnimationFrame(tick);
}
    };

    animationFrame = requestAnimationFrame(tick);
};

onMounted(() => {
    if (!statsPanel.value) {
return;
}

    statsObserver = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                animateStats();
                statsObserver?.disconnect();
                statsObserver = null;
            }
        },
        { threshold: 0.25 },
    );
    statsObserver.observe(statsPanel.value);
});

onBeforeUnmount(() => {
    statsObserver?.disconnect();
    cancelAnimationFrame(animationFrame);
});

const dayLabel = (day: string) => ({
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
}[day.toLowerCase()] ?? day);

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (hours && remainingMinutes) {
return `${hours} jam ${remainingMinutes} menit`;
}

    if (hours) {
return `${hours} jam`;
}

    return `${remainingMinutes} menit`;
};

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

const priceUnit = (facility: FacilityItem) => {
    switch (facility.slug.toLowerCase()) {
        case 'padel':
        case 'billiard':
            return '/ jam';
        case 'yoga':
        case 'pilates':
            return '/ sesi';
        case 'spinning':
            return '/ masuk';
        case 'ice-skating':
            return '/ jam';
        case 'gym':
            return ' · berbagai paket';
        default:
            return '';
    }
};

const heroImages: Record<string, string> = {
    padel: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1100&q=85',
    billiard: 'https://images.unsplash.com/photo-1606925797300-0b35e9d1794e?auto=format&fit=crop&w=1100&q=85',
    gym: 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1100&q=85',
    yoga: 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=1100&q=85',
    pilates: 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1100&q=85',
    spinning: 'https://images.unsplash.com/photo-1558611848-73f7eb4001a1?auto=format&fit=crop&w=1100&q=85',
    'ice-skating': 'https://images.unsplash.com/photo-1515703407324-5f753afd8be8?auto=format&fit=crop&w=1100&q=85',
};

const facilityImage = (facility: FacilityItem) =>
    heroImages[facility.slug.toLowerCase()] ??
    'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=900&q=80';

const facilityDescription = (slug: string) => {
    const key = slug.toLowerCase();

    if (key.includes('padel')) {
return 'Rasakan permainan yang seru bersama teman dan tim.';
}

    if (key.includes('billiard')) {
return 'Nikmati waktu santai dengan permainan yang kompetitif.';
}

    if (key.includes('gym')) {
return 'Bangun rutinitas latihan dan capai target kebugaranmu.';
}

    return 'Temukan olahraga yang sesuai dengan aktivitasmu.';
};
</script>

<template>
    <Head title="Sport Center — Olahraga, Lebih Mudah"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" /></Head>

    <PublicLayout>
    <div class="dream-home min-h-screen bg-white text-[#001428]">
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
                                Jelajahi olahraga <i class="fa-solid fa-arrow-down"></i>
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
                            <span class="text-lg font-extrabold text-[#001428]">{{ stats.facilities }} olahraga</span>
                            <span class="mt-2 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-[#a4da01]"></span>Siap untuk jadwal berikutnya</span>
                        </div>
                        <div class="hero-circle-label"><i class="fa-solid fa-bolt"></i><span>LET'S<br />PLAY</span></div>
                    </div>
                </div>
                <div class="hero-bottom-line"></div>
            </section>

            <section class="relative z-10 mx-auto -mt-1 max-w-7xl px-5 lg:px-8">
                <div ref="statsPanel" class="stats-panel grid grid-cols-2 gap-5 rounded-2xl border border-white/60 bg-white/65 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur-xl supports-[backdrop-filter]:bg-white/55 md:grid-cols-4 md:px-10 md:py-7">
                    <div class="stat-item"><span class="stat-number">{{ animatedStats.facilities }}</span><span class="stat-label">Jenis olahraga</span></div>
                    <div class="stat-item"><span class="stat-number">{{ animatedStats.courts }}</span><span class="stat-label">Lapangan tersedia</span></div>
                    <div class="stat-item"><span class="stat-number">{{ animatedStats.trainers }}</span><span class="stat-label">Pelatih terdaftar</span></div>
                    <div class="stat-item"><span class="stat-number">{{ animatedStats.membershipPackages }}</span><span class="stat-label">Paket keanggotaan</span></div>
                </div>
            </section>

            <section aria-labelledby="pilih-layanan-title" class="mx-auto max-w-7xl px-5 pt-16 lg:px-8 lg:pt-20">
                <div class="mb-6">
                    <p class="section-kicker">Mulai dari yang kamu butuhkan</p>
                    <h2 id="pilih-layanan-title" class="section-title">Mau <span>olahraga seperti apa?</span></h2>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <a href="#kelas" class="group rounded-2xl border border-sky-100 bg-sky-50/70 p-5 transition hover:-translate-y-1 hover:border-sky-200 hover:shadow-lg">
                        <span class="grid size-11 place-items-center rounded-xl bg-sky-100 text-sky-700"><i class="fa-solid fa-calendar-days"></i></span>
                        <p class="mt-4 text-xs font-black uppercase tracking-wider text-sky-700">Kelas terjadwal</p>
                        <h3 class="mt-1 text-lg font-extrabold text-[#001428]">Ikut kelas di jam tertentu</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Cek hari, jam, durasi, dan harga kelas sebelum memilih jadwal.</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-extrabold text-sky-800">Lihat jadwal kelas <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i></span>
                    </a>
                    <a href="#membership" class="group rounded-2xl border border-lime-200 bg-lime-50/70 p-5 transition hover:-translate-y-1 hover:border-lime-300 hover:shadow-lg">
                        <span class="grid size-11 place-items-center rounded-xl bg-lime-100 text-lime-800"><i class="fa-solid fa-id-card"></i></span>
                        <p class="mt-4 text-xs font-black uppercase tracking-wider text-lime-800">Membership</p>
                        <h3 class="mt-1 text-lg font-extrabold text-[#001428]">Akses dengan masa aktif</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Pilih paket keanggotaan berdasarkan fasilitas dan lama berlakunya.</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-extrabold text-lime-900">Lihat membership <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i></span>
                    </a>
                    <a href="#paket-trainer" class="group rounded-2xl border border-violet-100 bg-violet-50/70 p-5 transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-lg">
                        <span class="grid size-11 place-items-center rounded-xl bg-violet-100 text-violet-700"><i class="fa-solid fa-user-tie"></i></span>
                        <p class="mt-4 text-xs font-black uppercase tracking-wider text-violet-700">Paket personal trainer</p>
                        <h3 class="mt-1 text-lg font-extrabold text-[#001428]">Latihan privat per sesi</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Paket berisi sejumlah sesi latihan bersama trainer pilihanmu.</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-extrabold text-violet-800">Lihat paket trainer <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i></span>
                    </a>
                </div>
            </section>

            <section id="olahraga" class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-28">
                <div class="section-heading mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="section-kicker">Pilih permainanmu</p>
                        <h2 class="section-title">Temukan <span>olahraga favoritmu</span></h2>
                        <p class="mt-3 max-w-xl text-slate-500">Pilih olahraga yang kamu suka, cek tarifnya, lalu pesan waktu bermainmu.</p>
                    </div>
                    <Link href="/book" class="inline-flex items-center gap-2 self-start text-sm font-extrabold text-[#001428] hover:text-[#729700] sm:self-auto">Lihat semua olahraga <i class="fa-solid fa-arrow-right"></i></Link>
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
                                    <p class="mt-1 text-lg font-black text-[#001428]">{{ lowestPrice(facility) !== null ? formatCurrency(lowestPrice(facility) ?? 0) : 'Cek tarif' }}<span v-if="lowestPrice(facility) !== null" class="text-xs font-semibold text-slate-400"> {{ priceUnit(facility) }}</span></p>
                                </div>
                                <Link :href="`/book?facility=${encodeURIComponent(facility.slug)}`" class="btn-lime inline-flex items-center gap-2 rounded-full px-4 py-3 text-sm font-extrabold">Pesan <i class="fa-solid fa-arrow-right"></i></Link>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">Olahraga belum tersedia. Silakan cek kembali nanti.</div>
            </section>

            <section v-if="trainers.length" id="pelatih" class="bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                    <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="section-kicker">Berlatih bersama ahlinya</p>
                            <h2 class="section-title">Kenalan dengan <span>pelatih kami</span></h2>
                            <p class="mt-3 max-w-xl text-slate-500">Temukan dukungan latihan dari pelatih yang terdaftar di pusat olahraga kami.</p>
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <Link v-for="(trainer, index) in trainers" :key="trainer.id" :href="`/pelatih/${trainer.id}`" class="trainer-card group block rounded-2xl bg-white p-5 focus:outline-none focus:ring-2 focus:ring-[#a4da01]">
                            <div class="trainer-avatar" :class="'trainer-avatar-' + (index % 4)">
                                <i class="fa-solid fa-user-tie transition-transform duration-300 group-hover:scale-110"></i>
                            </div>
                            <p class="mt-5 text-xs font-extrabold uppercase tracking-[.15em] text-[#7a9d00]">Pelatih</p>
                            <h3 class="mt-2 text-lg font-extrabold">{{ trainer.name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ trainer.specialty || 'Pelatih olahraga' }}</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-[#001428] transition group-hover:text-[#729700]">Lihat detail <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i></span>
                        </Link>
                    </div>
                </div>
            </section>

            <section v-if="membershipPackages.length" id="membership" class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="section-kicker">Mulai perjalanan olahragamu</p>
                        <h2 class="section-title">Pilih <span>paket membership</span></h2>
                        <p class="mt-3 max-w-xl text-slate-500">Membership memberi akses ke fasilitas pilihan selama masa aktif paket. Paket trainer adalah pilihan sesi latihan terpisah.</p>
                    </div>
                </div>
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="(item, index) in membershipPackages" :key="item.id" class="group overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full bg-lime-100 px-3 py-1.5 text-xs font-extrabold text-lime-900"><i class="fa-solid fa-id-card mr-1"></i> Membership · {{ item.facility || 'Sport Center' }}</span>
                            <span class="text-xs font-bold text-slate-400">{{ String(index + 1).padStart(2, '0') }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-extrabold text-[#001428]">{{ item.name }}</h3>
                        <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ item.description }}</p>
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Masa aktif</p><p class="mt-1 font-extrabold text-[#001428]">{{ item.duration_days }} hari</p></div>
                            <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Jenis paket</p><p class="mt-1 font-extrabold text-[#001428]">Membership</p></div>
                        </div>
                        <div class="mt-5 border-t border-slate-100 pt-5">
                            <p class="text-xs font-semibold text-slate-500">Harga paket</p>
                            <p class="mt-1 text-2xl font-black text-[#001428]">{{ formatCurrency(item.price ?? 0) }}</p>
                            <button type="button" @click="openMembershipCheckout(item)" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#a4da01] px-5 py-3 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24] focus:outline-none focus:ring-2 focus:ring-[#789900] focus:ring-offset-2">
                                Pilih Membership <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </article>
                </div>
            </section>


            <section v-if="trainerPackages.length" id="paket-trainer" class="bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                    <div class="mb-10">
                        <p class="section-kicker">Latihan lebih terarah</p>
                        <h2 class="section-title">Pilih <span>paket personal trainer</span></h2>
                        <p class="mt-3 max-w-xl text-slate-500">Setiap paket berisi beberapa sesi latihan privat. Pilih jumlah sesi, masa berlaku, dan trainer yang cocok untukmu.</p>
                    </div>
                    <div class="mb-6 flex flex-wrap gap-2" aria-label="Saring paket trainer berdasarkan olahraga">
                        <button type="button" @click="selectedTrainerSport = 'Semua'" :aria-pressed="selectedTrainerSport === 'Semua'" class="rounded-full border px-4 py-2 text-sm font-bold transition" :class="selectedTrainerSport === 'Semua' ? 'border-violet-700 bg-violet-700 text-white' : 'border-violet-100 bg-white text-slate-600 hover:border-violet-300'">Semua olahraga</button>
                        <button v-for="sport in trainerPackageSports" :key="sport" type="button" @click="selectedTrainerSport = sport" :aria-pressed="selectedTrainerSport === sport" class="rounded-full border px-4 py-2 text-sm font-bold transition" :class="selectedTrainerSport === sport ? 'border-violet-700 bg-violet-700 text-white' : 'border-violet-100 bg-white text-slate-600 hover:border-violet-300'">{{ sport }}</button>
                    </div>
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="(item, index) in visibleTrainerPackages" :key="item.id" class="group overflow-hidden rounded-2xl border border-violet-100 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-extrabold text-violet-800"><i class="fa-solid fa-user-tie mr-1"></i> Sesi trainer · {{ item.sports[0] || 'Personal Trainer' }}</span>
                                <span class="text-xs font-bold text-slate-400">{{ String(index + 1).padStart(2, '0') }}</span>
                            </div>
                            <h3 class="mt-5 text-xl font-extrabold text-[#001428]">{{ item.name }}</h3>
                            <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ item.description || 'Program latihan personal bersama trainer.' }}</p>
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Jumlah sesi</p><p class="mt-1 font-extrabold text-[#001428]">{{ item.total_sessions }} sesi</p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Masa berlaku</p><p class="mt-1 font-extrabold text-[#001428]">{{ item.duration_days }} hari</p></div>
                            </div>
                            <div class="mt-5 border-t border-slate-100 pt-5">
                                <p class="text-xs font-semibold text-slate-500">Total paket<span v-if="item.total_sessions > 0"> · rata-rata {{ formatCurrency(item.price / item.total_sessions) }} / sesi</span></p>
                                <p class="mt-1 text-2xl font-black text-[#001428]">{{ formatCurrency(item.price) }}</p>
                                <button type="button" @click="openTrainerCheckout(item)" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#a4da01] px-5 py-3 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24]">
                                    Pilih Paket Trainer <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </article>
                    </div>
                </div>
            </section>


            <section v-if="classes.length" id="kelas" class="bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
                    <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="section-kicker">Kelas terjadwal · bukan paket sesi trainer</p>
                            <h2 class="section-title">Ikuti <span>kelas olahraga</span></h2>
                            <p class="mt-3 max-w-xl text-slate-500">Setiap kelas punya jadwal rutin sendiri. Cek hari, jam, dan harga per jadwal untuk memilih kelas yang pas.</p>
                        </div>
                        <Link href="/kelas" class="inline-flex items-center gap-2 self-start text-sm font-extrabold text-[#001428] transition hover:text-[#729700] sm:self-auto">
                            Lihat semua kelas <i class="fa-solid fa-arrow-right"></i>
                        </Link>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="(item, index) in classes" :key="item.id" class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-start justify-between gap-4 bg-[#001428] p-6 text-white">
                                <div>
                                    <span class="inline-flex rounded-full bg-sky-300/15 px-3 py-1.5 text-xs font-extrabold text-sky-200"><i class="fa-solid fa-calendar-days mr-1"></i> Kelas · {{ item.facility || 'Sport Center' }}</span>
                                    <h3 class="mt-4 text-xl font-black">{{ item.name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ item.description || 'Kelas olahraga bersama instruktur.' }}</p>
                                </div>
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white/10 text-[#b8ec24]">
                                    <i :class="index % 2 === 0 ? 'fa-solid fa-dumbbell' : 'fa-solid fa-heart-pulse'"></i>
                                </span>
                            </div>
                            <div class="p-6">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <p class="text-sm font-extrabold text-[#001428]">Jadwal kelas</p>
                                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-800">{{ item.schedules.length }} jadwal</span>
                                </div>
                                <div class="space-y-2">
                                    <div v-for="schedule in item.schedules.slice(0, 2)" :key="schedule.id" class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-xl bg-slate-50 px-3 py-2.5 text-sm">
                                        <span class="font-bold text-slate-700">{{ dayLabel(schedule.day) }} · {{ schedule.start_time }}–{{ schedule.end_time }}</span>
                                        <span class="font-extrabold text-[#001428]">{{ formatCurrency(schedule.price) }}</span>
                                    </div>
                                    <p v-if="item.schedules.length > 2" class="px-1 text-xs font-semibold text-slate-500">+{{ item.schedules.length - 2 }} jadwal lainnya</p>
                                </div>
                                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                    <p class="text-xs text-slate-500">Durasi <span class="font-bold text-[#001428]">{{ formatDuration(item.duration_minutes) }}</span></p>
                                    <Link href="/kelas" class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-5 py-3 text-sm font-black text-sky-900 transition hover:bg-sky-200">
                                        Pilih kelas <i class="fa-solid fa-arrow-right"></i>
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <Transition name="membership-modal">
                <div v-if="membershipModalOpen && selectedMembershipPackage" class="fixed inset-0 z-[120] flex items-center justify-center overflow-y-auto bg-[#001428]/75 px-4 py-6 backdrop-blur-sm" @click.self="closeMembershipCheckout">
                    <section role="dialog" aria-modal="true" aria-labelledby="membership-checkout-title" class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-[#789900]">Checkout membership</p>
                                <h2 id="membership-checkout-title" class="mt-2 text-2xl font-black text-[#001428]">{{ selectedMembershipPackage.name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ selectedMembershipPackage.facility }} · {{ selectedMembershipPackage.duration_days }} hari</p>
                            </div>
                            <button type="button" @click="closeMembershipCheckout" class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200" aria-label="Tutup modal"><i class="fa-solid fa-xmark"></i></button>
                        </div>

                        <div class="mt-7">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-extrabold text-[#001428]">1. Tambahkan paket trainer (opsional)</h3>
                                <span class="text-xs font-semibold text-slate-400">Boleh dilewati</span>
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button type="button" @click="selectedMembershipTrainerPackage = null; selectedMembershipTrainer = null" class="rounded-2xl border p-4 text-left transition" :class="!selectedMembershipTrainerPackage ? 'border-[#a4da01] bg-[#f7fbe9] ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'">
                                    <span class="font-extrabold text-[#001428]">Membership saja</span>
                                    <span class="mt-1 block text-sm text-slate-500">Tanpa tambahan personal trainer</span>
                                </button>
                                <button v-for="trainerPackage in trainerPackages.filter((item) => item.facility_id === selectedMembershipPackage?.facility_id)" :key="trainerPackage.id" type="button" @click="selectedMembershipTrainerPackage = trainerPackage; selectedMembershipTrainer = null" class="rounded-2xl border p-4 text-left transition" :class="selectedMembershipTrainerPackage?.id === trainerPackage.id ? 'border-[#a4da01] bg-[#f7fbe9] ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'">
                                    <span class="font-extrabold text-[#001428]">{{ trainerPackage.name }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ trainerPackage.total_sessions }} sesi · {{ trainerPackage.duration_days }} hari</span>
                                    <span class="mt-2 block text-sm font-black text-[#668500]">{{ formatCurrency(trainerPackage.price) }}</span>
                                </button>
                            </div>
                            <p v-if="!trainerPackages.some((item) => item.facility_id === selectedMembershipPackage?.facility_id)" class="mt-3 text-sm text-slate-500">Belum ada paket trainer untuk olahraga ini. Kamu tetap bisa membeli membership saja.</p>
                        </div>

                        <div v-if="selectedMembershipTrainerPackage" class="mt-7">
                            <h3 class="font-extrabold text-[#001428]">2. Pilih trainer</h3>
                            <div v-if="selectedMembershipTrainerPackage.trainers.length" class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button v-for="trainer in selectedMembershipTrainerPackage.trainers" :key="trainer.id" type="button" @click="selectedMembershipTrainer = trainer" class="rounded-2xl border p-4 text-left transition" :class="selectedMembershipTrainer?.id === trainer.id ? 'border-[#a4da01] bg-[#f7fbe9] ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'">
                                    <span class="font-extrabold text-[#001428]">{{ trainer.name }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ trainer.specialty || 'Personal trainer' }}</span>
                                </button>
                            </div>
                            <p v-else class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Belum ada trainer untuk paket ini. Pilih membership saja atau hubungi admin.</p>
                        </div>

                        <div class="mt-7">
                            <h3 class="font-extrabold text-[#001428]">{{ selectedMembershipTrainerPackage ? '3.' : '2.' }} Metode pembayaran</h3>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <button type="button" @click="membershipPaymentMethod = 'bank_transfer'" class="rounded-2xl border p-4 text-left" :class="membershipPaymentMethod === 'bank_transfer' ? 'border-[#a4da01] bg-[#f7fbe9]' : 'border-slate-200'"><i class="fa-solid fa-building-columns"></i><span class="mt-2 block text-sm font-bold">Transfer bank</span></button>
                                <button type="button" @click="membershipPaymentMethod = 'cash'" class="rounded-2xl border p-4 text-left" :class="membershipPaymentMethod === 'cash' ? 'border-[#a4da01] bg-[#f7fbe9]' : 'border-slate-200'"><i class="fa-solid fa-cash-register"></i><span class="mt-2 block text-sm font-bold">Bayar di kasir</span></button>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs text-slate-500">Total checkout</p>
                                <p class="text-2xl font-black text-[#001428]">{{ formatCurrency((selectedMembershipPackage.price ?? 0) + (selectedMembershipTrainerPackage?.price || 0)) }}</p>
                            </div>
                            <button type="button" :disabled="!!selectedMembershipTrainerPackage && (!selectedMembershipTrainer || !selectedMembershipTrainerPackage.trainers.length)" @click="continueMembershipCheckout" class="rounded-full bg-[#a4da01] px-6 py-3 font-black text-[#001428] disabled:cursor-not-allowed disabled:opacity-40">Lanjut checkout <i class="fa-solid fa-arrow-right ml-1"></i></button>
                        </div>
                    </section>
                </div>
            </Transition>

            <Transition name="membership-modal">
                <div v-if="trainerModalOpen && selectedTrainerPackage" class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-[#001428]/75 px-4 py-6 backdrop-blur-sm" @click.self="closeTrainerCheckout">
                    <section role="dialog" aria-modal="true" class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-[#789900]">Checkout personal trainer</p>
                                <h2 class="mt-2 text-2xl font-black text-[#001428]">{{ selectedTrainerPackage.name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ selectedTrainerPackage.sports[0] }} · {{ selectedTrainerPackage.total_sessions }} sesi · {{ selectedTrainerPackage.duration_days }} hari</p>
                            </div>
                            <button type="button" @click="closeTrainerCheckout" class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200" aria-label="Tutup modal"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="mt-7">
                            <h3 class="font-extrabold text-[#001428]">1. Pilih trainer</h3>
                            <div v-if="selectedTrainerPackage.trainers.length" class="mt-3 grid gap-3 sm:grid-cols-2">
                                <button v-for="trainer in selectedTrainerPackage.trainers" :key="trainer.id" type="button" @click="selectedTrainer = trainer" class="rounded-2xl border p-4 text-left transition" :class="selectedTrainer?.id === trainer.id ? 'border-[#a4da01] bg-[#f7fbe9] ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'">
                                    <span class="font-extrabold text-[#001428]">{{ trainer.name }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ trainer.specialty || 'Personal trainer' }}</span>
                                </button>
                            </div>
                            <p v-else class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Belum ada trainer yang tersedia untuk fasilitas ini.</p>
                        </div>
                        <div class="mt-7">
                            <h3 class="font-extrabold text-[#001428]">2. Metode pembayaran</h3>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <button type="button" @click="trainerPaymentMethod = 'bank_transfer'" class="rounded-2xl border p-4 text-left" :class="trainerPaymentMethod === 'bank_transfer' ? 'border-[#a4da01] bg-[#f7fbe9]' : 'border-slate-200'"><i class="fa-solid fa-building-columns"></i><span class="mt-2 block text-sm font-bold">Transfer bank</span></button>
                                <button type="button" @click="trainerPaymentMethod = 'cash'" class="rounded-2xl border p-4 text-left" :class="trainerPaymentMethod === 'cash' ? 'border-[#a4da01] bg-[#f7fbe9]' : 'border-slate-200'"><i class="fa-solid fa-cash-register"></i><span class="mt-2 block text-sm font-bold">Bayar di kasir</span></button>
                            </div>
                        </div>
                        <div class="mt-7 flex items-center justify-between border-t border-slate-100 pt-5">
                            <div><p class="text-xs text-slate-500">Total checkout</p><p class="text-2xl font-black text-[#001428]">{{ formatCurrency(selectedTrainerPackage.price) }}</p></div>
                            <button type="button" :disabled="!selectedTrainer || !selectedTrainerPackage.trainers.length" @click="continueTrainerCheckout" class="rounded-full bg-[#a4da01] px-6 py-3 font-black text-[#001428] disabled:cursor-not-allowed disabled:opacity-40">Lanjut checkout <i class="fa-solid fa-arrow-right ml-1"></i></button>
                        </div>
                    </section>
                </div>
            </Transition>

            <section class="px-5 pb-20 lg:px-8 lg:pb-28">
                <div class="cta-strip mx-auto flex max-w-7xl flex-col gap-6 rounded-3xl px-7 py-9 sm:flex-row sm:items-center sm:justify-between sm:px-12">
                    <div>
                        <p class="section-kicker">Mulai dari sekarang</p>
                        <h2 class="mt-2 text-2xl font-black sm:text-3xl">Siap untuk pertandingan berikutnya?</h2>
                        <p class="mt-2 text-sm text-slate-500">Pilih olahraga dan amankan jadwal bermainmu.</p>
                    </div>
                    <Link href="/book" class="btn-lime inline-flex shrink-0 items-center justify-center gap-3 rounded-full px-7 py-4 font-extrabold">Booking sekarang <i class="fa-solid fa-arrow-right"></i></Link>
                </div>
            </section>
        </main>
    </div>
    </PublicLayout>
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
.stats-panel { -webkit-backdrop-filter: blur(22px) saturate(155%); backdrop-filter: blur(22px) saturate(155%); background: rgba(255,255,255,.64); border-color: rgba(255,255,255,.78); box-shadow: 0 18px 48px rgba(0,20,40,.12), inset 0 1px 0 rgba(255,255,255,.8); }\n.stat-item { display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; }
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
