<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Clock, Loader2, MapPin } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Session = {
    id: number;
    session_date: string;
    day: string;
    start_time: string;
    end_time: string;
    trainer: string | null;
    trainer_specialty: string | null;
    capacity: number;
    registered_count: number;
    available_count: number;
    price: number;
};
type SportClass = {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    schedules: Session[];
};
type Facility = {
    id: number;
    name: string;
    slug: string;
    classes: SportClass[];
};

const props = defineProps<{
    facilities: Facility[];
    currentUser: { name: string; email: string } | null;
    success?: string | null;
    selectedScheduleId?: number | null;
}>();

const steps = [
    { id: 1, label: 'Pilih olahraga' },
    { id: 2, label: 'Pilih kelas' },
    { id: 3, label: 'Pilih sesi' },
    { id: 4, label: 'Data peserta' },
    { id: 5, label: 'Pembayaran' },
];

const step = ref(1);
const selectedFacility = ref<Facility | null>(null);
const selectedClass = ref<SportClass | null>(null);
const selected = ref<Session | null>(null);
const form = useForm({
    session_date: '',
    payment_method: 'bank_transfer' as 'bank_transfer' | 'cash',
    guest_name: props.currentUser?.name ?? '',
    guest_email: props.currentUser?.email ?? '',
    guest_phone: '',
});

const isGuest = computed(() => !props.currentUser);
const sessions = computed(() => selectedClass.value?.schedules ?? []);
const scheduleError = computed(() => form.errors.schedule ?? '');
const contactIsValid = computed(() => !isGuest.value || (
    !!form.guest_name.trim()
    && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.guest_email.trim())
    && !!form.guest_phone.trim()
));
const canContinue = computed(() => {
    if (step.value === 1) return !!selectedFacility.value;
    if (step.value === 2) return !!selectedClass.value;
    if (step.value === 3) return !!selected.value && selected.value.available_count > 0;
    if (step.value === 4) return contactIsValid.value;
    return true;
});

const money = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(value);

const dateLabel = (value: string) => new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
});
const dayLabel = (value: string) => ({
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
}[value.toLowerCase()] ?? value);

function chooseFacility(facility: Facility) {
    selectedFacility.value = facility;
    selectedClass.value = null;
    selected.value = null;
    form.clearErrors();
}
function chooseClass(sportClass: SportClass) {
    selectedClass.value = sportClass;
    selected.value = null;
    form.clearErrors();
}
function chooseSession(session: Session) {
    if (session.available_count < 1) return;
    selected.value = session;
    form.session_date = session.session_date;
    form.clearErrors();
}
function goBack() {
    if (step.value === 3 && selected) {
        // Keep the selected class when returning to the class list.
    }
    step.value = Math.max(1, step.value - 1);
}
function goNext() {
    if (!canContinue.value) return;
    step.value = Math.min(5, step.value + 1);
}
function submit() {
    if (!selected.value || selected.value.available_count < 1) return;
    form.session_date = selected.value.session_date;
    form.post(`/kelas/${selected.value.id}/register`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedFacility.value = null;
            selectedClass.value = null;
            selected.value = null;
            step.value = 1;
            form.reset('guest_phone');
        },
        onError: (errors) => {
            const fields = Object.keys(errors);
            if (fields.includes('schedule')) step.value = 3;
            else if (fields.some((field) => ['guest_name', 'guest_email', 'guest_phone'].includes(field))) step.value = 4;
        },
    });
}

onMounted(() => {
    if (!props.selectedScheduleId) return;
    for (const facility of props.facilities) {
        for (const sportClass of facility.classes) {
            const session = sportClass.schedules.find((item) => item.id === props.selectedScheduleId && item.available_count > 0);
            if (session) {
                selectedFacility.value = facility;
                selectedClass.value = sportClass;
                selected.value = session;
                form.session_date = session.session_date;
                step.value = 4;
                return;
            }
        }
    }
});
</script>

<template>
    <Head title="Pendaftaran Kelas" />
    <PublicLayout>
        <div class="min-h-screen bg-slate-50 text-slate-900">
            <main class="mx-auto w-full max-w-5xl px-4 py-8">
                <div class="mb-5">
                    <p class="text-xs font-bold tracking-[0.16em] text-lime-700 uppercase">Kelas terjadwal</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Pendaftaran kelas</h1>
                    <p class="mt-1 text-sm text-slate-500">Pilih olahraga, kelas, dan sesi yang ingin kamu ikuti.</p>
                </div>

                <section v-if="props.success" class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_80px_rgba(15,23,42,0.12)]">
                    <div class="bg-[#001428] px-6 py-12 text-center text-white sm:px-12 sm:py-16">
                        <div class="mx-auto flex size-24 items-center justify-center rounded-full bg-[#a4da01] text-[#001428]">
                            <Check class="size-12" :stroke-width="3" />
                        </div>
                        <p class="mt-7 text-sm font-semibold tracking-[0.22em] text-[#c9f36a] uppercase">Konfirmasi berhasil</p>
                        <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Pendaftaran kelas berhasil!</h2>
                        <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-200">{{ props.success }}</p>
                    </div>
                    <div class="flex flex-col gap-3 px-6 py-8 sm:flex-row sm:justify-center">
                        <a href="/" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke beranda</a>
                        <a href="/kelas" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] hover:bg-lime-300">Daftar kelas lainnya <ArrowRight class="ml-2 size-4" /></a>
                    </div>
                </section>

                <template v-else>
                    <nav aria-label="Langkah pendaftaran" class="mt-6 flex flex-wrap items-center gap-2">
                        <template v-for="(item, index) in steps" :key="item.id">
                            <div class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition-colors"
                                :class="step === item.id ? 'bg-[#a4da01] text-[#001428]' : step > item.id ? 'bg-slate-200 text-slate-700' : 'bg-white text-slate-400'"
                                :aria-current="step === item.id ? 'step' : undefined">
                                <span class="flex size-5 items-center justify-center rounded-full text-xs"
                                    :class="step > item.id ? 'bg-[#001428] text-white' : step === item.id ? 'bg-white/70' : 'bg-slate-100'">
                                    <Check v-if="step > item.id" class="size-3" />
                                    <template v-else>{{ item.id }}</template>
                                </span>
                                {{ item.label }}
                            </div>
                            <ArrowRight v-if="index < steps.length - 1" class="size-4 text-slate-400" />
                        </template>
                    </nav>

                    <section v-if="step === 1" class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                        <div class="mb-5">
                            <h2 class="font-semibold">Pilih olahraga</h2>
                            <p class="mt-1 text-xs text-slate-500">Pilih fasilitas olahraga yang menyediakan kelas.</p>
                        </div>
                        <div v-if="facilities.length" class="grid gap-3 sm:grid-cols-2">
                            <button v-for="facility in facilities" :key="facility.id" type="button"
                                class="rounded-2xl border p-5 text-left transition hover:border-lime-300 hover:bg-lime-50/40"
                                :class="selectedFacility?.id === facility.id ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200' "
                                @click="chooseFacility(facility)">
                                <p class="text-xs font-bold tracking-wider text-lime-800 uppercase">Olahraga</p>
                                <h3 class="mt-2 text-xl font-bold">{{ facility.name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ facility.classes.length }} kelas tersedia</p>
                                <span class="mt-4 inline-flex items-center text-sm font-semibold text-lime-800">Pilih olahraga <ArrowRight class="ml-2 size-4" /></span>
                            </button>
                        </div>
                        <div v-else class="rounded-xl bg-slate-50 px-5 py-12 text-center">
                            <h3 class="font-semibold text-slate-800">Belum ada olahraga yang tersedia</h3>
                            <p class="mt-1 text-sm text-slate-500">Kelas online akan muncul di sini setelah jadwal tersedia.</p>
                        </div>
                        <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                            <button type="button" :disabled="!canContinue" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-40" @click="goNext">Lanjut pilih kelas <ArrowRight class="ml-2 size-4" /></button>
                        </div>
                    </section>

                    <section v-else-if="step === 2" class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold">Pilih kelas</h2>
                                <p class="mt-1 text-xs text-slate-500">Kelas yang tersedia untuk {{ selectedFacility?.name }}.</p>
                            </div>
                            <button type="button" class="text-sm font-semibold text-lime-800 hover:text-lime-950" @click="step = 1">Ganti olahraga</button>
                        </div>
                        <div v-if="selectedFacility?.classes.length" class="grid gap-3 sm:grid-cols-2">
                            <button v-for="sportClass in selectedFacility.classes" :key="sportClass.id" type="button"
                                class="rounded-2xl border p-5 text-left transition hover:border-lime-300 hover:bg-lime-50/40"
                                :class="selectedClass?.id === sportClass.id ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200'"
                                @click="chooseClass(sportClass)">
                                <p class="text-xs font-bold tracking-wider text-lime-800 uppercase">{{ selectedFacility.name }}</p>
                                <h3 class="mt-2 text-xl font-bold">{{ sportClass.name }}</h3>
                                <p v-if="sportClass.description" class="mt-2 line-clamp-2 text-sm leading-5 text-slate-500">{{ sportClass.description }}</p>
                                <p class="mt-3 text-xs text-slate-500">{{ sportClass.schedules.length }} sesi tersedia dalam 6 minggu ke depan</p>
                                <span class="mt-4 inline-flex items-center text-sm font-semibold text-lime-800">Pilih kelas <ArrowRight class="ml-2 size-4" /></span>
                            </button>
                        </div>
                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
                            <button type="button" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700" @click="goBack"><ArrowLeft class="mr-2 size-4" /> Kembali</button>
                            <button type="button" :disabled="!canContinue" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-40" @click="goNext">Lanjut pilih sesi <ArrowRight class="ml-2 size-4" /></button>
                        </div>
                    </section>

                    <section v-else-if="step === 3" class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="font-semibold">Pilih sesi kelas</h2>
                                <p class="mt-1 text-xs text-slate-500">{{ selectedFacility?.name }} · {{ selectedClass?.name }}</p>
                            </div>
                            <span class="rounded-full bg-lime-50 px-3 py-1.5 text-xs font-semibold text-lime-800">{{ sessions.length }} sesi tersedia</span>
                        </div>
                        <p v-if="scheduleError" role="alert" class="mb-4 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700">{{ scheduleError }} Pilih sesi lain yang masih tersedia.</p>
                        <div v-if="sessions.length" class="grid gap-3 md:grid-cols-2">
                            <button v-for="session in sessions" :key="session.id + '-' + session.session_date" type="button" :disabled="session.available_count < 1"
                                class="rounded-2xl border p-4 text-left transition hover:border-lime-300 hover:bg-lime-50/40 disabled:cursor-not-allowed disabled:opacity-60"
                                :class="selected?.id === session.id && selected?.session_date === session.session_date ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200'"
                                @click="chooseSession(session)">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-full bg-lime-50 px-2.5 py-1 text-[11px] font-bold text-lime-800">{{ dayLabel(session.day) }}</span>
                                    <span v-if="session.available_count < 1" class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700">Kuota penuh</span>
                                    <span v-else class="text-xs font-semibold text-slate-500">{{ session.available_count }} tempat tersisa</span>
                                </div>
                                <h3 class="mt-3 text-lg font-bold">{{ dateLabel(session.session_date) }}</h3>
                                <p class="mt-1 flex items-center gap-2 text-sm text-slate-600"><Clock class="size-4 text-slate-400" />{{ session.start_time }}–{{ session.end_time }}</p>
                                <p class="mt-2 flex items-center gap-2 text-sm text-slate-600"><MapPin class="size-4 text-slate-400" />{{ session.trainer || 'Trainer akan diumumkan' }}<span v-if="session.trainer_specialty" class="text-slate-400">· {{ session.trainer_specialty }}</span></p>
                                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                    <div><p class="text-xs text-slate-400">Harga per peserta</p><p class="font-bold">{{ money(session.price) }}</p></div>
                                    <span v-if="selected?.id === session.id && selected?.session_date === session.session_date" class="inline-flex items-center gap-1 text-sm font-semibold text-lime-800"><Check class="size-4" /> Dipilih</span>
                                    <span v-else class="text-sm font-semibold text-lime-800">{{ session.available_count < 1 ? 'Tidak tersedia' : 'Pilih sesi' }}</span>
                                </div>
                            </button>
                        </div>
                        <div v-else class="rounded-xl bg-slate-50 px-5 py-12 text-center">
                            <h3 class="font-semibold text-slate-800">Belum ada sesi untuk kelas ini</h3>
                            <p class="mt-1 text-sm text-slate-500">Silakan pilih kelas lain.</p>
                        </div>
                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
                            <button type="button" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700" @click="goBack"><ArrowLeft class="mr-2 size-4" /> Kembali</button>
                            <button type="button" :disabled="!canContinue" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-40" @click="goNext">Lanjut ke data peserta <ArrowRight class="ml-2 size-4" /></button>
                        </div>
                    </section>

                    <div v-else class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <section class="rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                            <div v-if="step === 4" class="space-y-5">
                                <div>
                                    <h2 class="font-semibold">Data peserta</h2>
                                    <p class="mt-1 text-sm text-slate-500">Masukkan data peserta untuk pendaftaran kelas.</p>
                                </div>
                                <div v-if="isGuest" class="rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm leading-6 text-sky-900">Kamu mendaftar sebagai tamu. Tidak perlu login atau membuat akun untuk mengikuti kelas.</div>
                                <div v-else class="rounded-xl border border-lime-200 bg-lime-50/70 p-4 text-sm leading-6 text-lime-950">Pendaftaran akan ditautkan ke akun {{ props.currentUser?.email }}.</div>
                                <div class="space-y-4">
                                    <div>
                                        <label for="guest-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama lengkap</label>
                                        <input id="guest-name" v-model="form.guest_name" type="text" maxlength="255" required :readonly="!isGuest" autocomplete="name" class="w-full rounded-xl border border-slate-200 px-3 py-3 outline-none read-only:bg-slate-50 focus:border-lime-500 focus:ring-2 focus:ring-lime-100" placeholder="Nama peserta" />
                                        <p v-if="form.errors.guest_name" class="mt-1 text-xs text-red-600">{{ form.errors.guest_name }}</p>
                                    </div>
                                    <div>
                                        <label for="guest-email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                                        <input id="guest-email" v-model="form.guest_email" type="email" maxlength="255" required :readonly="!isGuest" autocomplete="email" class="w-full rounded-xl border border-slate-200 px-3 py-3 outline-none read-only:bg-slate-50 focus:border-lime-500 focus:ring-2 focus:ring-lime-100" placeholder="nama@email.com" />
                                        <p v-if="form.errors.guest_email" class="mt-1 text-xs text-red-600">{{ form.errors.guest_email }}</p>
                                    </div>
                                    <div>
                                        <label for="guest-phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor HP</label>
                                        <input id="guest-phone" v-model="form.guest_phone" type="tel" maxlength="50" :required="isGuest" autocomplete="tel" class="w-full rounded-xl border border-slate-200 px-3 py-3 outline-none focus:border-lime-500 focus:ring-2 focus:ring-lime-100" placeholder="08xxxxxxxxxx" />
                                        <p v-if="form.errors.guest_phone" class="mt-1 text-xs text-red-600">{{ form.errors.guest_phone }}</p>
                                    </div>
                                </div>
                            </div>

                            <form v-else class="space-y-6" @submit.prevent="submit">
                                <div>
                                    <h2 class="font-semibold">Metode pembayaran</h2>
                                    <p class="mt-1 text-sm text-slate-500">Pilih cara pembayaran untuk pendaftaran ini.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button type="button" :aria-pressed="form.payment_method === 'bank_transfer'" class="rounded-2xl border p-4 text-left transition" :class="form.payment_method === 'bank_transfer' ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'" @click="form.payment_method = 'bank_transfer'">
                                        <span class="grid size-10 place-items-center rounded-xl bg-white shadow-sm">🏦</span>
                                        <span class="mt-3 block font-semibold">Transfer bank</span>
                                        <span class="mt-1 block text-sm text-slate-500">Lanjutkan pembayaran melalui transfer.</span>
                                    </button>
                                    <button type="button" :aria-pressed="form.payment_method === 'cash'" class="rounded-2xl border p-4 text-left transition" :class="form.payment_method === 'cash' ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200 hover:border-slate-300'" @click="form.payment_method = 'cash'">
                                        <span class="grid size-10 place-items-center rounded-xl bg-white shadow-sm">💵</span>
                                        <span class="mt-3 block font-semibold">Tunai di tempat</span>
                                        <span class="mt-1 block text-sm text-slate-500">Bayar langsung di kasir Sport Center.</span>
                                    </button>
                                </div>
                                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Pendaftaran akan dicatat setelah dikirim. Simpan bukti transaksi dan tunggu konfirmasi dari pengelola.</div>
                                <p v-if="form.errors.payment_method" role="alert" class="text-sm text-red-600">{{ form.errors.payment_method }}</p>
                            </form>

                            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
                                <button type="button" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700" @click="goBack"><ArrowLeft class="mr-2 size-4" /> Kembali</button>
                                <button v-if="step === 4" type="button" :disabled="!canContinue" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-40" @click="goNext">Lanjut ke pembayaran <ArrowRight class="ml-2 size-4" /></button>
                                <button v-else type="button" :disabled="form.processing" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-50" @click="submit">
                                    <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                                    {{ form.processing ? 'Memproses pendaftaran...' : 'Konfirmasi pendaftaran' }}
                                    <Check v-if="!form.processing" class="ml-2 size-4" />
                                </button>
                            </div>
                        </section>

                        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.04)] lg:sticky lg:top-24">
                            <p class="text-xs font-bold tracking-[0.16em] text-slate-500 uppercase">Ringkasan pendaftaran</p>
                            <h2 class="mt-2 text-lg font-bold">Detail sesi</h2>
                            <div v-if="selected" class="mt-5 space-y-4 text-sm">
                                <div><p class="text-slate-500">Olahraga</p><p class="mt-1 font-semibold">{{ selectedFacility?.name }}</p></div>
                                <div><p class="text-slate-500">Kelas</p><p class="mt-1 font-semibold">{{ selectedClass?.name }}</p></div>
                                <div><p class="text-slate-500">Tanggal & waktu</p><p class="mt-1 font-semibold">{{ dateLabel(selected.session_date) }}</p><p class="mt-0.5 text-slate-500">{{ dayLabel(selected.day) }} · {{ selected.start_time }}–{{ selected.end_time }}</p></div>
                                <div><p class="text-slate-500">Instruktur</p><p class="mt-1 font-semibold">{{ selected.trainer || 'Trainer akan diumumkan' }}</p></div>
                                <div v-if="step === 5"><p class="text-slate-500">Peserta</p><p class="mt-1 font-semibold">{{ form.guest_name || 'Belum diisi' }}</p><p class="mt-0.5 text-slate-500">{{ form.guest_email || 'Email belum diisi' }}</p></div>
                                <div class="border-t border-slate-200 pt-4"><p class="text-slate-500">Total pembayaran</p><p class="mt-1 text-2xl font-bold">{{ money(selected.price) }}</p><p class="mt-1 text-xs text-slate-500">Harga untuk satu peserta</p></div>
                            </div>
                            <p v-else class="mt-4 text-sm text-slate-500">Pilih olahraga, kelas, dan sesi terlebih dahulu.</p>
                        </aside>
                    </div>
                </template>
            </main>
        </div>
    </PublicLayout>
</template>
