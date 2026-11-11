<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    Clock,
    Loader2,
    MapPin,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Schedule = {
    id: number;
    session_date: string;
    class_name: string;
    description: string | null;
    facility: string | null;
    trainer: string | null;
    trainer_specialty: string | null;
    day: string;
    start_time: string;
    end_time: string;
    capacity: number;
    registered_count: number;
    available_count: number;
    price: number;
};

const props = defineProps<{
    schedules: Schedule[];
    currentUser: { name: string; email: string } | null;
    success?: string | null;
    selectedScheduleId?: number | null;
}>();

const steps = [
    { id: 1, label: 'Pilih jadwal' },
    { id: 2, label: 'Data peserta' },
    { id: 3, label: 'Pembayaran' },
];

const step = ref(1);
const selected = ref<Schedule | null>(null);
const form = useForm({
    session_date: '',
    payment_method: 'bank_transfer' as 'bank_transfer' | 'cash',
    guest_name: props.currentUser?.name ?? '',
    guest_email: props.currentUser?.email ?? '',
    guest_phone: '',
});

const isGuest = computed(() => !props.currentUser);
const scheduleError = computed(
    () =>
        Object.entries(form.errors).find(
            ([field]) => field === 'schedule',
        )?.[1] ?? '',
);
const contactIsValid = computed(() => {
    if (!isGuest.value) {
        return true;
    }

    return (
        !!form.guest_name.trim() &&
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.guest_email.trim()) &&
        !!form.guest_phone.trim()
    );
});
const canContinue = computed(() => {
    if (step.value === 1) {
        return !!selected.value && selected.value.available_count > 0;
    }

    return step.value !== 2 || contactIsValid.value;
});

const money = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const dateLabel = (value: string) =>
    new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });

const dayLabel = (value: string) =>
    ({
        monday: 'Senin',
        tuesday: 'Selasa',
        wednesday: 'Rabu',
        thursday: 'Kamis',
        friday: 'Jumat',
        saturday: 'Sabtu',
        sunday: 'Minggu',
    })[value.toLowerCase()] ?? value;

onMounted(() => {
    if (!props.selectedScheduleId) {
        return;
    }

    const target = props.schedules.find(
        (schedule) => schedule.id === props.selectedScheduleId,
    );

    if (target && target.available_count > 0) {
        selectSchedule(target);
        step.value = 2;
    }
});

function selectSchedule(schedule: Schedule) {
    if (schedule.available_count < 1) {
        return;
    }

    selected.value = schedule;
    form.session_date = schedule.session_date;
    form.clearErrors();
}

function goBack() {
    step.value = Math.max(1, step.value - 1);
}

function goNext() {
    if (!canContinue.value) {
        return;
    }

    step.value = Math.min(3, step.value + 1);
}

function submit() {
    if (!selected.value || selected.value.available_count < 1) {
        return;
    }

    form.session_date = selected.value.session_date;
    form.post(`/kelas/${selected.value.id}/register`, {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = null;
            step.value = 1;
            form.reset('guest_phone');
        },
        onError: (errors) => {
            const fields = Object.keys(errors);

            if (fields.includes('schedule')) {
                step.value = 1;
            } else if (
                fields.some((field) =>
                    ['guest_name', 'guest_email', 'guest_phone'].includes(
                        field,
                    ),
                )
            ) {
                step.value = 2;
            }
        },
    });
}
</script>

<template>
    <Head title="Pendaftaran Kelas" />
    <PublicLayout>
        <div class="min-h-screen bg-slate-50 text-slate-900">
            <main class="mx-auto w-full max-w-5xl px-4 py-8">
                <div class="mb-5">
                    <p
                        class="text-xs font-bold tracking-[0.16em] text-lime-700 uppercase"
                    >
                        Kelas terjadwal
                    </p>
                    <h1
                        class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Pendaftaran kelas
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih sesi, lengkapi data peserta, lalu konfirmasi
                        pendaftaran.
                    </p>
                </div>

                <section
                    v-if="props.success"
                    class="overflow-hidden rounded-3xl bg-white shadow-[0_20px_80px_rgba(15,23,42,0.12)]"
                >
                    <div
                        class="relative overflow-hidden bg-[#001428] px-6 py-12 text-center text-white sm:px-12 sm:py-16"
                    >
                        <div
                            class="pointer-events-none absolute -top-16 -right-12 size-56 rounded-full border-[30px] border-white/5"
                        ></div>
                        <div
                            class="relative mx-auto flex size-24 items-center justify-center rounded-full bg-[#a4da01] text-[#001428] shadow-lg shadow-black/20 sm:size-28"
                        >
                            <Check
                                class="size-12 sm:size-14"
                                :stroke-width="3"
                            />
                        </div>
                        <p
                            class="relative mt-7 text-sm font-semibold tracking-[0.22em] text-[#c9f36a] uppercase"
                        >
                            Konfirmasi berhasil
                        </p>
                        <h2
                            class="relative mt-3 text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            Pendaftaran kelas berhasil!
                        </h2>
                        <p
                            class="relative mx-auto mt-4 max-w-xl text-base leading-7 text-slate-200"
                        >
                            {{ props.success }}
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-3 px-6 py-8 sm:flex-row sm:justify-center sm:px-12"
                    >
                        <a
                            href="/"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >Kembali ke beranda</a
                        >
                        <a
                            href="/kelas"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] transition hover:bg-lime-300"
                            >Daftar kelas lainnya
                            <ArrowRight class="ml-2 size-4"
                        /></a>
                    </div>
                </section>

                <template v-else>
                    <nav
                        aria-label="Langkah pendaftaran"
                        class="mt-6 flex flex-wrap items-center gap-2"
                    >
                        <template v-for="(item, index) in steps" :key="item.id">
                            <div
                                class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition-colors"
                                :class="
                                    step === item.id
                                        ? 'bg-[#a4da01] text-[#001428]'
                                        : step > item.id
                                          ? 'bg-slate-200 text-slate-700'
                                          : 'bg-white text-slate-400'
                                "
                                :aria-current="
                                    step === item.id ? 'step' : undefined
                                "
                            >
                                <span
                                    class="flex size-5 items-center justify-center rounded-full text-xs"
                                    :class="
                                        step > item.id
                                            ? 'bg-[#001428] text-white'
                                            : step === item.id
                                              ? 'bg-white/70'
                                              : 'bg-slate-100'
                                    "
                                >
                                    <Check
                                        v-if="step > item.id"
                                        class="size-3"
                                    />
                                    <template v-else>{{ item.id }}</template>
                                </span>
                                {{ item.label }}
                            </div>
                            <ArrowRight
                                v-if="index < steps.length - 1"
                                class="size-4 text-slate-400"
                            />
                        </template>
                    </nav>

                    <section
                        v-if="step === 1"
                        class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6"
                    >
                        <div
                            class="mb-5 flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h2 class="font-semibold">
                                    Pilih jadwal kelas
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Pilih tanggal sesi yang ingin kamu ikuti.
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-lime-50 px-3 py-1.5 text-xs font-semibold text-lime-800"
                            >
                                {{ schedules.length }} sesi tersedia
                            </span>
                        </div>

                        <p
                            v-if="scheduleError"
                            role="alert"
                            class="mb-4 rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700"
                        >
                            {{ scheduleError }} Pilih jadwal lain yang masih
                            tersedia.
                        </p>

                        <div
                            v-if="schedules.length"
                            class="grid gap-3 md:grid-cols-2"
                        >
                            <button
                                v-for="schedule in schedules"
                                :key="schedule.id + '-' + schedule.session_date"
                                type="button"
                                :disabled="schedule.available_count < 1"
                                :aria-pressed="
                                    selected?.id === schedule.id &&
                                    selected?.session_date ===
                                        schedule.session_date
                                "
                                class="rounded-2xl border p-4 text-left transition hover:border-lime-300 hover:bg-lime-50/40 disabled:cursor-not-allowed disabled:opacity-60"
                                :class="
                                    selected?.id === schedule.id &&
                                    selected?.session_date ===
                                        schedule.session_date
                                        ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30'
                                        : 'border-slate-200 bg-white'
                                "
                                @click="selectSchedule(schedule)"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <span
                                        class="rounded-full bg-lime-50 px-2.5 py-1 text-[11px] font-bold text-lime-800"
                                    >
                                        {{
                                            schedule.facility || 'Sport Center'
                                        }}
                                    </span>
                                    <span
                                        v-if="schedule.available_count < 1"
                                        class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700"
                                        >Kuota penuh</span
                                    >
                                    <span
                                        v-else
                                        class="text-xs font-semibold text-slate-500"
                                        >{{ schedule.available_count }} tempat
                                        tersisa</span
                                    >
                                </div>
                                <h3
                                    class="mt-3 text-lg font-bold tracking-tight text-slate-900"
                                >
                                    {{ schedule.class_name }}
                                </h3>
                                <p
                                    v-if="schedule.description"
                                    class="mt-1 line-clamp-2 text-sm leading-5 text-slate-500"
                                >
                                    {{ schedule.description }}
                                </p>
                                <div
                                    class="mt-4 space-y-2 text-sm text-slate-600"
                                >
                                    <p class="flex items-center gap-2">
                                        <Clock
                                            class="size-4 shrink-0 text-slate-400"
                                        />
                                        {{ dateLabel(schedule.session_date) }} ·
                                        {{ schedule.start_time }}–{{
                                            schedule.end_time
                                        }}
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <MapPin
                                            class="size-4 shrink-0 text-slate-400"
                                        />
                                        {{
                                            schedule.trainer ||
                                            'Trainer akan diumumkan'
                                        }}
                                        <span
                                            v-if="schedule.trainer_specialty"
                                            class="text-slate-400"
                                            >·
                                            {{
                                                schedule.trainer_specialty
                                            }}</span
                                        >
                                    </p>
                                </div>
                                <div
                                    class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3"
                                >
                                    <div>
                                        <p class="text-xs text-slate-400">
                                            Harga per peserta
                                        </p>
                                        <p class="font-bold text-slate-900">
                                            {{ money(schedule.price) }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="
                                            selected?.id === schedule.id &&
                                            selected?.session_date ===
                                                schedule.session_date
                                        "
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-lime-800"
                                        ><Check class="size-4" /> Dipilih</span
                                    >
                                    <span
                                        v-else
                                        class="text-sm font-semibold text-lime-800"
                                    >
                                        {{
                                            schedule.available_count < 1
                                                ? 'Tidak tersedia'
                                                : 'Pilih sesi'
                                        }}
                                    </span>
                                </div>
                            </button>
                        </div>
                        <div
                            v-else
                            class="rounded-xl bg-slate-50 px-5 py-12 text-center"
                        >
                            <div class="text-3xl">🧘</div>
                            <h3 class="mt-3 font-semibold text-slate-800">
                                Belum ada jadwal kelas
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Jadwal kelas akan muncul di sini ketika sudah
                                tersedia.
                            </p>
                        </div>

                        <div
                            class="mt-6 flex justify-end border-t border-slate-100 pt-5"
                        >
                            <button
                                type="button"
                                :disabled="!canContinue"
                                class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-40"
                                @click="goNext"
                            >
                                Lanjut ke data peserta
                                <ArrowRight class="ml-2 size-4" />
                            </button>
                        </div>
                    </section>

                    <div
                        v-else
                        class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(260px,0.85fr)]"
                    >
                        <section
                            class="rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6"
                        >
                            <div v-if="step === 2">
                                <div class="mb-6">
                                    <h2 class="text-lg font-semibold">
                                        Data peserta
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Pastikan informasi kontakmu aktif untuk
                                        menerima konfirmasi.
                                    </p>
                                </div>

                                <div
                                    v-if="isGuest"
                                    class="mb-5 rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm leading-6 text-slate-600"
                                >
                                    Kamu mendaftar sebagai tamu. Tidak perlu
                                    membuat akun untuk mengikuti kelas.
                                </div>
                                <div
                                    v-else
                                    class="mb-5 rounded-xl border border-lime-200 bg-lime-50/70 p-4 text-sm leading-6 text-lime-950"
                                >
                                    Pendaftaran akan ditautkan ke akun
                                    {{ props.currentUser?.email }}.
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            for="guest-name"
                                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                                            >Nama lengkap</label
                                        >
                                        <input
                                            id="guest-name"
                                            v-model="form.guest_name"
                                            type="text"
                                            maxlength="255"
                                            required
                                            :readonly="!isGuest"
                                            autocomplete="name"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-3 transition outline-none read-only:bg-slate-50 focus:border-lime-500 focus:ring-2 focus:ring-lime-100"
                                            placeholder="Nama peserta"
                                        />
                                        <p
                                            v-if="form.errors.guest_name"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ form.errors.guest_name }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="guest-email"
                                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                                            >Email</label
                                        >
                                        <input
                                            id="guest-email"
                                            v-model="form.guest_email"
                                            type="email"
                                            maxlength="255"
                                            required
                                            :readonly="!isGuest"
                                            autocomplete="email"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-3 transition outline-none read-only:bg-slate-50 focus:border-lime-500 focus:ring-2 focus:ring-lime-100"
                                            placeholder="nama@email.com"
                                        />
                                        <p
                                            v-if="form.errors.guest_email"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ form.errors.guest_email }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="guest-phone"
                                            class="mb-1.5 block text-sm font-semibold text-slate-700"
                                        >
                                            Nomor HP
                                            <span
                                                v-if="!isGuest"
                                                class="font-normal text-slate-400"
                                                >(opsional)</span
                                            >
                                        </label>
                                        <input
                                            id="guest-phone"
                                            v-model="form.guest_phone"
                                            type="tel"
                                            maxlength="50"
                                            :required="isGuest"
                                            autocomplete="tel"
                                            placeholder="08xxxxxxxxxx"
                                            class="w-full rounded-xl border border-slate-200 px-3 py-3 transition outline-none focus:border-lime-500 focus:ring-2 focus:ring-lime-100"
                                        />
                                        <p
                                            v-if="form.errors.guest_phone"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ form.errors.guest_phone }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="goBack"
                                    >
                                        <ArrowLeft class="mr-2 size-4" /> Ganti
                                        jadwal
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="!canContinue"
                                        class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-40"
                                        @click="goNext"
                                    >
                                        Lanjut ke pembayaran
                                        <ArrowRight class="ml-2 size-4" />
                                    </button>
                                </div>
                            </div>

                            <form
                                v-else
                                class="space-y-6"
                                @submit.prevent="submit"
                            >
                                <div>
                                    <h2 class="text-lg font-semibold">
                                        Metode pembayaran
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Pilih cara pembayaran untuk pendaftaran
                                        ini.
                                    </p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <button
                                        type="button"
                                        :aria-pressed="
                                            form.payment_method ===
                                            'bank_transfer'
                                        "
                                        class="rounded-2xl border p-4 text-left transition"
                                        :class="
                                            form.payment_method ===
                                            'bank_transfer'
                                                ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30'
                                                : 'border-slate-200 hover:border-slate-300'
                                        "
                                        @click="
                                            form.payment_method =
                                                'bank_transfer'
                                        "
                                    >
                                        <span
                                            class="grid size-10 place-items-center rounded-xl bg-white text-slate-700 shadow-sm"
                                            ><span aria-hidden="true"
                                                >🏦</span
                                            ></span
                                        >
                                        <span
                                            class="mt-3 block font-semibold text-slate-900"
                                            >Transfer bank</span
                                        >
                                        <span
                                            class="mt-1 block text-sm text-slate-500"
                                            >Lanjutkan pembayaran melalui
                                            transfer.</span
                                        >
                                    </button>
                                    <button
                                        type="button"
                                        :aria-pressed="
                                            form.payment_method === 'cash'
                                        "
                                        class="rounded-2xl border p-4 text-left transition"
                                        :class="
                                            form.payment_method === 'cash'
                                                ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30'
                                                : 'border-slate-200 hover:border-slate-300'
                                        "
                                        @click="form.payment_method = 'cash'"
                                    >
                                        <span
                                            class="grid size-10 place-items-center rounded-xl bg-white text-slate-700 shadow-sm"
                                            ><span aria-hidden="true"
                                                >💵</span
                                            ></span
                                        >
                                        <span
                                            class="mt-3 block font-semibold text-slate-900"
                                            >Tunai di tempat</span
                                        >
                                        <span
                                            class="mt-1 block text-sm text-slate-500"
                                            >Bayar langsung di kasir Sport
                                            Center.</span
                                        >
                                    </button>
                                </div>
                                <div
                                    class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-900"
                                >
                                    Pendaftaran akan dicatat setelah dikirim.
                                    Simpan bukti transaksi dan tunggu konfirmasi
                                    dari pengelola.
                                </div>
                                <p
                                    v-if="form.errors.payment_method"
                                    role="alert"
                                    class="text-sm text-red-600"
                                >
                                    {{ form.errors.payment_method }}
                                </p>
                                <div
                                    class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="goBack"
                                    >
                                        <ArrowLeft class="mr-2 size-4" />
                                        Kembali
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Loader2
                                            v-if="form.processing"
                                            class="mr-2 size-4 animate-spin"
                                        />
                                        <span>{{
                                            form.processing
                                                ? 'Memproses pendaftaran...'
                                                : 'Konfirmasi pendaftaran'
                                        }}</span>
                                        <ArrowRight
                                            v-if="!form.processing"
                                            class="ml-2 size-4"
                                        />
                                    </button>
                                </div>
                            </form>
                        </section>

                        <aside
                            class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.04)] lg:sticky lg:top-24"
                        >
                            <p
                                class="text-xs font-bold tracking-[0.16em] text-slate-500 uppercase"
                            >
                                Ringkasan pendaftaran
                            </p>
                            <h2 class="mt-2 text-lg font-bold text-slate-900">
                                Detail sesi
                            </h2>
                            <div v-if="selected" class="mt-5 space-y-4 text-sm">
                                <div>
                                    <p class="text-slate-500">Kelas</p>
                                    <p
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{ selected.class_name }}
                                    </p>
                                    <p class="mt-0.5 text-slate-500">
                                        {{
                                            selected.facility || 'Sport Center'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-500">
                                        Tanggal & waktu
                                    </p>
                                    <p
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{ dateLabel(selected.session_date) }}
                                    </p>
                                    <p class="mt-0.5 text-slate-500">
                                        {{ dayLabel(selected.day) }} ·
                                        {{ selected.start_time }}–{{
                                            selected.end_time
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Instruktur</p>
                                    <p
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{
                                            selected.trainer ||
                                            'Trainer akan diumumkan'
                                        }}
                                    </p>
                                </div>
                                <div v-if="step === 3">
                                    <p class="text-slate-500">Peserta</p>
                                    <p
                                        class="mt-1 font-semibold text-slate-900"
                                    >
                                        {{ form.guest_name || 'Belum diisi' }}
                                    </p>
                                    <p class="mt-0.5 text-slate-500">
                                        {{
                                            form.guest_email ||
                                            'Email belum diisi'
                                        }}
                                    </p>
                                </div>
                                <div class="border-t border-slate-200 pt-4">
                                    <p class="text-slate-500">
                                        Total pembayaran
                                    </p>
                                    <p
                                        class="mt-1 text-2xl font-bold text-slate-900"
                                    >
                                        {{ money(selected.price) }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Harga untuk satu peserta
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-lime-800 hover:text-lime-950"
                                    @click="step = 1"
                                >
                                    Ganti jadwal
                                </button>
                            </div>
                            <p v-else class="mt-4 text-sm text-slate-500">
                                Pilih sesi kelas terlebih dahulu.
                            </p>
                        </aside>
                    </div>
                </template>
            </main>
        </div>
    </PublicLayout>
</template>
