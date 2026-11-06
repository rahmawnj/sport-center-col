<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Trainer = { id: number; name: string; specialty: string | null };
type MembershipPackage = {
    id: number;
    facility_id: number;
    name: string;
    duration_days: number;
    facility: string | null;
    facility_slug: string | null;
    price: number;
    description: string;
};
type TrainerPackage = {
    id: number;
    facility_id: number;
    facility: string | null;
    name: string;
    total_sessions: number;
    duration_days: number;
    price: number;
    description: string | null;
    trainers: Trainer[];
};
type CurrentUser = { name: string; email: string } | null;

const props = defineProps<{
    packages: MembershipPackage[];
    trainerPackages: TrainerPackage[];
    currentUser: CurrentUser;
}>();

const STORAGE_KEY = 'sport-center-membership-selection-v1';
const selectedPackage = ref<MembershipPackage | null>(null);
const selectedTrainerPackage = ref<TrainerPackage | null>(null);
const selectedTrainer = ref<Trainer | null>(null);
const readyPackages = computed(() => props.packages.filter((item) => item.price !== null));
const availableTrainerPackages = computed(() => selectedPackage.value
    ? props.trainerPackages.filter((item) => item.facility_id === selectedPackage.value?.facility_id)
    : []);
const totalPrice = computed(() => (selectedPackage.value?.price ?? 0) + (selectedTrainerPackage.value?.price ?? 0));
const canContinue = computed(() => !!selectedPackage.value
    && (!selectedTrainerPackage.value || !!selectedTrainer.value));

const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(value);

const durationLabel = (days: number) => {
    if (days >= 365 && days % 365 === 0) return days / 365 + ' tahun';
    if (days >= 30 && days % 30 === 0) return days / 30 + ' bulan';
    return days + ' hari';
};

const saveSelection = () => {
    if (!selectedPackage.value) {
        localStorage.removeItem(STORAGE_KEY);
        return;
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        package_id: selectedPackage.value.id,
        trainer_package_id: selectedTrainerPackage.value?.id ?? null,
        trainer_id: selectedTrainer.value?.id ?? null,
    }));
};

const selectMembership = (item: MembershipPackage) => {
    selectedPackage.value = item;
    selectedTrainerPackage.value = null;
    selectedTrainer.value = null;
    saveSelection();
    window.setTimeout(() => document.getElementById('pilih-trainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
};

const selectTrainerPackage = (item: TrainerPackage) => {
    selectedTrainerPackage.value = item;
    selectedTrainer.value = null;
    saveSelection();
    window.setTimeout(() => document.getElementById('pilih-pelatih')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
};

const skipTrainer = () => {
    selectedTrainerPackage.value = null;
    selectedTrainer.value = null;
    saveSelection();
};

const selectTrainer = (item: Trainer) => {
    selectedTrainer.value = item;
    saveSelection();
};

const continueRegistration = () => {
    if (!canContinue.value || !selectedPackage.value) return;

    const params: Record<string, number> = { package_id: selectedPackage.value.id };
    if (selectedTrainerPackage.value) params.trainer_package_id = selectedTrainerPackage.value.id;
    if (selectedTrainer.value) params.trainer_id = selectedTrainer.value.id;

    saveSelection();
    router.get('/membership/register', params, { preserveState: false, preserveScroll: false });
};

const resetSelection = () => {
    selectedPackage.value = null;
    selectedTrainerPackage.value = null;
    selectedTrainer.value = null;
    localStorage.removeItem(STORAGE_KEY);
};

onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return;

        const selection = JSON.parse(saved) as {
            package_id?: number;
            trainer_package_id?: number | null;
            trainer_id?: number | null;
        };
        const membership = props.packages.find((item) => item.id === Number(selection.package_id));
        if (!membership) {
            localStorage.removeItem(STORAGE_KEY);
            return;
        }

        selectedPackage.value = membership;
        const trainerPackage = props.trainerPackages.find((item) =>
            item.id === Number(selection.trainer_package_id)
            && item.facility_id === membership.facility_id
        );
        if (trainerPackage) {
            selectedTrainerPackage.value = trainerPackage;
            selectedTrainer.value = trainerPackage.trainers.find((item) => item.id === Number(selection.trainer_id)) ?? null;
        }
        saveSelection();
    } catch {
        localStorage.removeItem(STORAGE_KEY);
    }
});
</script>

<template>
    <Head title="Daftar Membership — DreamSports" />
    <PublicLayout>
        <main class="membership-page">
            <section class="membership-hero relative overflow-hidden">
                <div class="hero-orbit hero-orbit-one"></div>
                <div class="hero-orbit hero-orbit-two"></div>
                <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-24">
                    <div>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[.06] px-4 py-2 text-xs font-bold tracking-wide text-white/80">
                            <span class="h-2 w-2 rounded-full bg-[#a4da01]"></span> MEMBERSHIP DREAMSPORTS
                        </div>
                        <h1 class="max-w-2xl text-4xl font-black leading-[1.08] tracking-[-.045em] text-white sm:text-5xl lg:text-6xl">
                            Mulai perjalanan<br /><span class="text-[#b7e934]">olahragamu.</span>
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                            Pilih membership, tambahkan program personal trainer bila perlu, lalu lanjutkan pendaftaran. Semua pilihan akan ikut tersimpan.
                        </p>
                        <a href="#pilih-paket" class="mt-8 inline-flex items-center gap-3 rounded-full bg-[#a4da01] px-6 py-3.5 font-extrabold text-[#001428] transition hover:bg-[#b8ec24]">
                            Mulai pilih paket <i class="fa-solid fa-arrow-down"></i>
                        </a>
                    </div>
                    <div class="hero-visual relative mx-auto w-full max-w-lg">
                        <div class="hero-image overflow-hidden rounded-[2rem] border border-white/15 bg-white/5 p-3 shadow-2xl shadow-black/25">
                            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1100&q=85" alt="Area latihan gym modern" class="h-[300px] w-full rounded-[1.5rem] object-cover sm:h-[370px]" />
                            <div class="flex items-center justify-between gap-4 px-2 pb-1 pt-5">
                                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-[#b7e934]">Your next chapter</p><p class="mt-1 text-lg font-extrabold text-white">Small steps. Stronger you.</p></div>
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#a4da01] text-xl text-[#001428]"><i class="fa-solid fa-bolt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pilih-paket" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-16 sm:py-20 lg:px-8">
                <div class="mb-10 text-center">
                    <p class="section-kicker">Langkah 01</p>
                    <h2 class="section-title">Pilih paket <span>membership</span></h2>
                    <p class="mx-auto mt-3 max-w-2xl leading-7 text-slate-500">Tentukan fasilitas olahraga dan masa membership yang ingin kamu ambil.</p>
                </div>
                <div v-if="readyPackages.length" class="grid items-stretch gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="(item, index) in readyPackages" :key="item.id" class="package-card relative flex flex-col rounded-3xl border bg-white p-6 sm:p-7" :class="selectedPackage?.id === item.id ? 'border-[#a4da01] ring-2 ring-[#a4da01]/30' : 'border-slate-200'">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-700"><i :class="item.name.toLowerCase().includes('gym') ? 'fa-solid fa-dumbbell' : 'fa-solid fa-medal'" class="text-lg"></i></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ durationLabel(item.duration_days) }}</span>
                        </div>
                        <p class="text-xs font-extrabold uppercase tracking-[.16em] text-[#789900]">{{ item.facility || 'DreamSports' }}</p>
                        <h3 class="mt-2 text-2xl font-black tracking-tight text-[#001428]">{{ item.name }}</h3>
                        <p class="mt-3 min-h-[48px] text-sm leading-6 text-slate-500">{{ item.description }}</p>
                        <div class="my-7 border-y border-slate-100 py-6">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Harga membership</p>
                            <p class="mt-2 text-3xl font-black tracking-tight text-[#001428]">{{ formatCurrency(item.price) }}</p>
                            <p class="mt-2 text-sm text-slate-500">Berlaku {{ durationLabel(item.duration_days) }}</p>
                        </div>
                        <button type="button" class="mt-auto flex w-full items-center justify-center gap-3 rounded-full px-5 py-3.5 font-extrabold transition" :class="selectedPackage?.id === item.id ? 'bg-[#001428] text-white' : 'bg-[#a4da01] text-[#001428] hover:bg-[#b8ec24]'" @click="selectMembership(item)">
                            <i :class="selectedPackage?.id === item.id ? 'fa-solid fa-check' : 'fa-solid fa-arrow-right'"></i>
                            {{ selectedPackage?.id === item.id ? 'Paket dipilih' : 'Pilih membership' }}
                        </button>
                    </article>
                </div>
                <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                    <h3 class="mt-4 text-xl font-extrabold text-[#001428]">Paket membership belum tersedia</h3>
                    <p class="mt-2 text-sm text-slate-500">Belum ada facility yang membuka pendaftaran membership secara online.</p>
                </div>
            </section>

            <section v-if="selectedPackage" id="pilih-trainer" class="scroll-mt-24 bg-[#f6f8f2]">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:py-20 lg:px-8">
                    <div class="mb-9">
                        <p class="section-kicker">Langkah 02 · Opsional</p>
                        <h2 class="section-title">Tambahkan paket <span>personal trainer</span></h2>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-500">Pilih program latihan tambahan dari {{ selectedPackage.facility }}. Paket trainer tidak wajib dan tidak mengubah paket membership.</p>
                    </div>
                    <div v-if="availableTrainerPackages.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="item in availableTrainerPackages" :key="item.id" class="rounded-3xl border bg-white p-6 transition" :class="selectedTrainerPackage?.id === item.id ? 'border-[#a4da01] ring-2 ring-[#a4da01]/30' : 'border-slate-200'">
                            <div class="flex items-start justify-between gap-3"><span class="rounded-full bg-[#eff6d9] px-3 py-1.5 text-xs font-extrabold text-[#668500]">Personal trainer</span><i v-if="selectedTrainerPackage?.id === item.id" class="fa-solid fa-circle-check text-xl text-[#789900]"></i></div>
                            <h3 class="mt-5 text-xl font-black text-[#001428]">{{ item.name }}</h3>
                            <p class="mt-2 min-h-10 text-sm leading-6 text-slate-500">{{ item.description || 'Program latihan terarah bersama personal trainer.' }}</p>
                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Jumlah sesi</p><p class="mt-1 font-extrabold text-[#001428]">{{ item.total_sessions }} sesi</p></div>
                                <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Masa berlaku</p><p class="mt-1 font-extrabold text-[#001428]">{{ durationLabel(item.duration_days) }}</p></div>
                            </div>
                            <div class="mt-5 border-t border-slate-100 pt-5"><p class="text-xs font-bold text-slate-500">Harga add-on</p><p class="mt-1 text-2xl font-black text-[#001428]">{{ formatCurrency(item.price) }}</p></div>
                            <button type="button" :disabled="!item.trainers.length" class="mt-5 w-full rounded-full px-5 py-3 font-extrabold transition disabled:cursor-not-allowed disabled:opacity-40" :class="selectedTrainerPackage?.id === item.id ? 'bg-[#001428] text-white' : 'bg-[#a4da01] text-[#001428] hover:bg-[#b8ec24]'" @click="selectTrainerPackage(item)">
                                {{ !item.trainers.length ? 'Belum ada trainer tersedia' : selectedTrainerPackage?.id === item.id ? 'Paket dipilih' : 'Pilih paket trainer' }}
                            </button>
                        </article>
                    </div>
                    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Belum ada paket trainer aktif untuk facility ini. Kamu tetap bisa melanjutkan tanpa add-on.</div>
                    <button type="button" class="mt-6 text-sm font-bold text-slate-600 underline decoration-dashed underline-offset-4 hover:text-[#789900]" @click="skipTrainer">Lewati, tanpa add-on trainer</button>
                </div>
            </section>

            <section v-if="selectedTrainerPackage" id="pilih-pelatih" class="scroll-mt-24">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:py-20 lg:px-8">
                    <div class="mb-9">
                        <p class="section-kicker">Langkah 03</p>
                        <h2 class="section-title">Pilih <span>personal trainer</span></h2>
                        <p class="mt-3 max-w-2xl leading-7 text-slate-500">Pilih trainer yang terdaftar di {{ selectedPackage?.facility }}. Trainer baru disimpan sebagai pilihan setelah kamu menyelesaikan pendaftaran.</p>
                    </div>
                    <div v-if="selectedTrainerPackage.trainers.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <button v-for="trainer in selectedTrainerPackage.trainers" :key="trainer.id" type="button" class="flex items-center gap-4 rounded-2xl border bg-white p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md" :class="selectedTrainer?.id === trainer.id ? 'border-[#a4da01] ring-2 ring-[#a4da01]/30' : 'border-slate-200'" @click="selectTrainer(trainer)">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#eff6d9] text-xl text-[#668500]"><i class="fa-solid fa-user-tie"></i></span>
                            <span class="min-w-0 flex-1"><span class="block font-extrabold text-[#001428]">{{ trainer.name }}</span><span class="mt-1 block text-sm text-slate-500">{{ trainer.specialty || 'Personal trainer' }}</span></span>
                            <i v-if="selectedTrainer?.id === trainer.id" class="fa-solid fa-circle-check text-xl text-[#789900]"></i>
                        </button>
                    </div>
                </div>
            </section>

            <section v-if="selectedPackage" class="bg-[#001428]">
                <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 lg:grid-cols-[1fr_auto] lg:items-center lg:px-8">
                    <div class="text-white">
                        <p class="text-xs font-extrabold uppercase tracking-[.18em] text-[#b7e934]">Ringkasan pilihan</p>
                        <h2 class="mt-2 text-2xl font-black">{{ selectedPackage.name }}</h2>
                        <p class="mt-1 text-sm text-slate-300">{{ selectedPackage.facility }} · {{ durationLabel(selectedPackage.duration_days) }}</p>
                        <div class="mt-4 space-y-1 text-sm text-slate-300">
                            <p v-if="selectedTrainerPackage">Add-on: {{ selectedTrainerPackage.name }} · {{ selectedTrainer?.name || 'Pilih trainer terlebih dahulu' }}</p>
                            <p v-else>Tanpa add-on trainer</p>
                        </div>
                    </div>
                    <div class="min-w-[250px]">
                        <div class="mb-4 flex items-end justify-between gap-6 text-white"><span class="text-sm text-slate-300">Estimasi total</span><strong class="text-2xl font-black">{{ formatCurrency(totalPrice) }}</strong></div>
                        <button type="button" :disabled="!canContinue" class="flex w-full items-center justify-center gap-3 rounded-full bg-[#a4da01] px-7 py-4 font-black text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-40" @click="continueRegistration">
                            Lanjut ke pendaftaran <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="button" class="mt-3 w-full text-xs font-semibold text-slate-400 underline underline-offset-4 hover:text-white" @click="resetSelection">Reset semua pilihan</button>
                    </div>
                </div>
            </section>

            <section v-if="!selectedPackage" class="mx-auto max-w-7xl px-5 pb-16 text-center">
                <p class="text-sm text-slate-500">Pilih paket membership terlebih dahulu untuk melihat pilihan add-on trainer.</p>
            </section>

            <section class="mx-auto max-w-5xl px-5 py-14">
                <div class="rounded-3xl bg-[#f6f8f2] px-6 py-8 text-center sm:px-12">
                    <h2 class="text-2xl font-black text-[#001428]">Sudah punya akun?</h2>
                    <p class="mt-2 text-sm text-slate-500">Kamu tetap bisa memilih membership dan add-on, lalu masuk menggunakan akun member yang sudah ada.</p>
                    <Link href="/login" class="mt-5 inline-flex rounded-full border border-slate-300 bg-white px-6 py-3 font-bold text-[#001428] hover:border-[#a4da01]">Masuk ke akun</Link>
                </div>
            </section>
        </main>
    </PublicLayout>
</template>

<style scoped>
.membership-page { --lime: #a4da01; --navy: #001428; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
.membership-hero { background: var(--navy); }
.hero-orbit { position: absolute; border: 1px solid rgba(164,218,1,.15); border-radius: 50%; pointer-events: none; }
.hero-orbit-one { top: -340px; right: -150px; width: 760px; height: 760px; box-shadow: 0 0 0 60px rgba(164,218,1,.025), 0 0 0 120px rgba(164,218,1,.02); }
.hero-orbit-two { right: 16%; bottom: -400px; width: 600px; height: 600px; }
.hero-image { background: rgba(255,255,255,.04); }
.section-kicker { color: #789900; font-size: 11px; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; }
.section-title { margin-top: 10px; color: var(--navy); font-size: clamp(28px, 3vw, 40px); font-weight: 900; line-height: 1.16; letter-spacing: -.04em; }
.section-title span { color: #7da500; }
.package-card { box-shadow: 0 12px 35px rgba(0,20,40,.045); transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
.package-card:hover { transform: translateY(-5px); box-shadow: 0 24px 50px rgba(0,20,40,.1); }
@media (prefers-reduced-motion: reduce) { .package-card { transition: none; } }
</style>
