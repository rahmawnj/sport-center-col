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
const membershipModalOpen = ref(false);
const modalStep = ref<1 | 2 | 3>(1);
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
    // Hapus pilihan checkout sebelumnya sebelum memulai pesanan baru.
    localStorage.removeItem(STORAGE_KEY);
    selectedPackage.value = item;
    selectedTrainerPackage.value = null;
    selectedTrainer.value = null;
    modalStep.value = 1;
    membershipModalOpen.value = true;
    saveSelection();
};

const selectTrainerPackage = (item: TrainerPackage) => {
    selectedTrainerPackage.value = item;
    selectedTrainer.value = null;
    saveSelection();
};

const closeMembershipModal = () => {
    membershipModalOpen.value = false;
};

const continueToTrainerPackages = () => {
    modalStep.value = 2;
};

const continueToTrainerSelection = () => {
    if (!selectedTrainerPackage.value) return;
    modalStep.value = 3;
};

const skipTrainer = () => {
    selectedTrainerPackage.value = null;
    selectedTrainer.value = null;
    saveSelection();
    continueRegistration();
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
    // Akun yang sudah login memulai checkout baru tanpa mewarisi pilihan
    // guest yang mungkin masih tertinggal dari sesi pendaftaran sebelumnya.
    if (props.currentUser) {
        localStorage.removeItem(STORAGE_KEY);
        return;
    }

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
                    <p class="section-kicker">Pilih sesuai kebutuhanmu</p>
                    <h2 class="section-title">Pilih paket <span>membership</span></h2>
                    <p class="mx-auto mt-3 max-w-2xl leading-7 text-slate-500">Klik tombol pada paket untuk melihat detailnya dan melanjutkan pendaftaran melalui satu jendela pilihan.</p>
                </div>
                <div v-if="readyPackages.length" class="grid items-stretch gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="item in readyPackages" :key="item.id" class="package-card relative flex flex-col rounded-3xl border bg-white p-6 sm:p-7" :class="selectedPackage?.id === item.id ? 'border-[#a4da01] ring-2 ring-[#a4da01]/30' : 'border-slate-200'">
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
                            <i class="fa-solid fa-arrow-right"></i> Pilih membership
                        </button>
                    </article>
                </div>
                <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                    <h3 class="mt-4 text-xl font-extrabold text-[#001428]">Paket membership belum tersedia</h3>
                    <p class="mt-2 text-sm text-slate-500">Belum ada facility yang membuka pendaftaran membership secara online.</p>
                </div>
            </section>

            <Transition name="membership-modal">
                <div v-if="membershipModalOpen && selectedPackage" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-[#001428]/75 px-4 py-6 backdrop-blur-sm" @click.self="closeMembershipModal">
                    <section role="dialog" aria-modal="true" aria-labelledby="membership-modal-title" class="relative my-auto max-h-[92vh] w-full max-w-2xl overflow-hidden rounded-[1.75rem] bg-white shadow-2xl">
                        <header class="flex items-center justify-between border-b border-slate-100 px-6 py-5 sm:px-8">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[.2em] text-[#789900]">Pendaftaran membership</p>
                                <h2 id="membership-modal-title" class="mt-1 text-xl font-black text-[#001428]">
                                    {{ modalStep === 1 ? 'Detail paket membership' : modalStep === 2 ? 'Paket personal trainer' : 'Pilih personal trainer' }}
                                </h2>
                            </div>
                            <button type="button" aria-label="Tutup modal" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200" @click="closeMembershipModal">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </header>

                        <div class="max-h-[calc(92vh-170px)] overflow-y-auto px-6 py-6 sm:px-8">
                            <div class="mb-6 flex items-center gap-2">
                                <span v-for="step in 3" :key="step" class="h-1.5 flex-1 rounded-full transition" :class="modalStep >= step ? 'bg-[#a4da01]' : 'bg-slate-100'"></span>
                            </div>

                            <div v-if="modalStep === 1">
                                <div class="rounded-2xl bg-[#f6f8f2] p-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-extrabold uppercase tracking-[.16em] text-[#789900]">{{ selectedPackage.facility || 'DreamSports' }}</p>
                                            <h3 class="mt-2 text-2xl font-black text-[#001428]">{{ selectedPackage.name }}</h3>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-slate-600">{{ durationLabel(selectedPackage.duration_days) }}</span>
                                    </div>
                                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ selectedPackage.description || 'Paket membership untuk menikmati fasilitas olahraga.' }}</p>
                                    <div class="mt-6 border-t border-slate-200 pt-5">
                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Harga membership</p>
                                        <p class="mt-1 text-3xl font-black text-[#001428]">{{ formatCurrency(selectedPackage.price) }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Masa aktif {{ durationLabel(selectedPackage.duration_days) }}</p>
                                    </div>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-slate-500">Pastikan paket dan fasilitas yang dipilih sudah sesuai. Pada langkah berikutnya, kamu bisa menambahkan paket personal trainer secara opsional.</p>
                            </div>

                            <div v-else-if="modalStep === 2">
                                <p class="mb-5 text-sm leading-6 text-slate-500">Pilih paket trainer untuk {{ selectedPackage.facility || 'fasilitas ini' }}. Add-on ini opsional dan tidak mengubah paket membership.</p>
                                <div v-if="availableTrainerPackages.length" class="space-y-3">
                                    <button v-for="item in availableTrainerPackages" :key="item.id" type="button" class="w-full rounded-2xl border p-4 text-left transition" :class="selectedTrainerPackage?.id === item.id ? 'border-[#a4da01] bg-[#f7fbe9] ring-1 ring-[#a4da01]' : 'border-slate-200 hover:border-slate-300' " @click="selectTrainerPackage(item)">
                                        <span class="flex items-start justify-between gap-3">
                                            <span>
                                                <span class="block font-extrabold text-[#001428]">{{ item.name }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ item.total_sessions }} sesi · {{ durationLabel(item.duration_days) }}</span>
                                            </span>
                                            <span class="shrink-0 text-right">
                                                <span class="block font-black text-[#001428]">{{ formatCurrency(item.price) }}</span>
                                                <i v-if="selectedTrainerPackage?.id === item.id" class="fa-solid fa-circle-check mt-2 text-[#789900]"></i>
                                            </span>
                                        </span>
                                        <span v-if="item.description" class="mt-3 block text-sm leading-6 text-slate-500">{{ item.description }}</span>
                                    </button>
                                </div>
                                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">Belum ada paket trainer aktif untuk fasilitas ini. Kamu bisa langsung melanjutkan tanpa add-on.</div>
                            </div>

                            <div v-else>
                                <p class="mb-5 text-sm leading-6 text-slate-500">Pilih trainer untuk paket <strong class="text-[#001428]">{{ selectedTrainerPackage?.name }}</strong>.</p>
                                <div v-if="selectedTrainerPackage?.trainers.length" class="space-y-3">
                                    <button v-for="trainer in selectedTrainerPackage.trainers" :key="trainer.id" type="button" class="flex w-full items-center gap-4 rounded-2xl border p-4 text-left transition" :class="selectedTrainer?.id === trainer.id ? 'border-[#a4da01] bg-[#f7fbe9] ring-1 ring-[#a4da01]' : 'border-slate-200 hover:border-slate-300'" @click="selectTrainer(trainer)">
                                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#eff6d9] text-lg text-[#668500]"><i class="fa-solid fa-user-tie"></i></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-extrabold text-[#001428]">{{ trainer.name }}</span>
                                            <span class="mt-1 block text-sm text-slate-500">{{ trainer.specialty || 'Personal trainer' }}</span>
                                        </span>
                                        <i v-if="selectedTrainer?.id === trainer.id" class="fa-solid fa-circle-check text-xl text-[#789900]"></i>
                                    </button>
                                </div>
                                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">Belum ada trainer yang tersedia untuk paket ini.</div>
                            </div>

                            <div class="mt-6 rounded-2xl bg-[#001428] p-5 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold uppercase tracking-[.16em] text-[#b7e934]">Ringkasan pilihan</p>
                                        <p class="mt-2 truncate font-bold">{{ selectedPackage.name }}</p>
                                        <p class="mt-1 text-xs text-slate-300">{{ selectedTrainerPackage ? selectedTrainerPackage.name : 'Tanpa add-on trainer' }}<span v-if="selectedTrainer"> · {{ selectedTrainer.name }}</span></p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Total</p>
                                        <p class="mt-1 text-lg font-black">{{ formatCurrency(totalPrice) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <footer class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                            <button v-if="modalStep > 1" type="button" class="rounded-full px-5 py-3 text-sm font-bold text-slate-500 transition hover:bg-slate-100 hover:text-[#001428]" @click="modalStep = modalStep === 3 ? 2 : 1">
                                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                            </button>
                            <button v-else type="button" class="rounded-full px-5 py-3 text-sm font-bold text-slate-500 transition hover:bg-slate-100 hover:text-[#001428]" @click="closeMembershipModal">Nanti dulu</button>

                            <button v-if="modalStep === 1" type="button" class="rounded-full bg-[#a4da01] px-7 py-3.5 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24]" @click="continueToTrainerPackages">
                                Lanjutkan <i class="fa-solid fa-arrow-right ml-2"></i>
                            </button>
                            <div v-else-if="modalStep === 2" class="flex flex-col gap-2 sm:flex-row">
                                <button type="button" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50" @click="skipTrainer">Tanpa add-on</button>
                                <button type="button" :disabled="!selectedTrainerPackage" class="rounded-full bg-[#a4da01] px-6 py-3 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-40" @click="continueToTrainerSelection">
                                    Pilih trainer <i class="fa-solid fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                            <button v-else type="button" :disabled="!selectedTrainer" class="rounded-full bg-[#a4da01] px-7 py-3.5 text-sm font-black text-[#001428] transition hover:bg-[#b8ec24] disabled:cursor-not-allowed disabled:opacity-40" @click="continueRegistration">
                                Lanjut ke pendaftaran <i class="fa-solid fa-arrow-right ml-2"></i>
                            </button>
                        </footer>
                    </section>
                </div>
            </Transition>

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
.membership-modal-enter-active, .membership-modal-leave-active { transition: opacity .2s ease; }
.membership-modal-enter-active section, .membership-modal-leave-active section { transition: transform .2s ease; }
.membership-modal-enter-from, .membership-modal-leave-to { opacity: 0; }
.membership-modal-enter-from section, .membership-modal-leave-to section { transform: translateY(14px) scale(.98); }
.section-kicker { color: #789900; font-size: 11px; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; }
.section-title { margin-top: 10px; color: var(--navy); font-size: clamp(28px, 3vw, 40px); font-weight: 900; line-height: 1.16; letter-spacing: -.04em; }
.section-title span { color: #7da500; }
.package-card { box-shadow: 0 12px 35px rgba(0,20,40,.045); transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
.package-card:hover { transform: translateY(-5px); box-shadow: 0 24px 50px rgba(0,20,40,.1); }
@media (prefers-reduced-motion: reduce) { .package-card { transition: none; } }
</style>
