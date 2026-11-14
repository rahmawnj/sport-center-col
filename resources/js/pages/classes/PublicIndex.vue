<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Clock, Loader2, MapPin } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

defineOptions({ layout: null });

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
    { id: 3, label: 'Pilih jadwal & tanggal' },
    { id: 5, label: 'Data peserta' },
    { id: 6, label: 'Konfirmasi' },
    { id: 7, label: 'Pembayaran' },
];

const step = ref(1);
const selectedFacility = ref<Facility | null>(null);
const selectedClass = ref<SportClass | null>(null);
const selectedSchedule = ref<Session | null>(null);
const selectedDate = ref<string | null>(null);
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
const schedulesByDay = computed(() => {
    const uniqueSchedules = new Map<number, Session>();
    for (const session of sessions.value) {
        if (!uniqueSchedules.has(session.id)) uniqueSchedules.set(session.id, session);
    }

    const groups = new Map<string, Session[]>();
    for (const schedule of uniqueSchedules.values()) {
        const day = schedule.day.toLowerCase();
        if (!groups.has(day)) groups.set(day, []);
        groups.get(day)!.push(schedule);
    }

    const dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    return [...groups.entries()]
        .sort(([a], [b]) => dayOrder.indexOf(a) - dayOrder.indexOf(b))
        .map(([day, schedules]) => ({
            day,
            schedules: schedules.sort((a, b) => a.start_time.localeCompare(b.start_time)),
        }));
});
const dates = computed(() => sessions.value
    .filter((session) => session.id === selectedSchedule.value?.id)
    .sort((a, b) => a.session_date.localeCompare(b.session_date)));
const scheduleError = computed(() => form.errors.schedule ?? '');
const contactIsValid = computed(() => !isGuest.value || (
    !!form.guest_name.trim()
    && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.guest_email.trim())
    && !!form.guest_phone.trim()
));
const canContinue = computed(() => {
    if (step.value === 1) return !!selectedFacility.value;
    if (step.value === 2) return !!selectedClass.value;
    if (step.value === 3) return !!selectedSchedule.value;
    if (step.value === 4) return !!selected.value && selected.value.available_count > 0;
    if (step.value === 5) return contactIsValid.value;
    if (step.value === 6) return true;
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
    selectedSchedule.value = null;
    selectedDate.value = null;
    selected.value = null;
    form.clearErrors();
}
function chooseClass(sportClass: SportClass) {
    selectedClass.value = sportClass;
    selectedSchedule.value = null;
    selectedDate.value = null;
    selected.value = null;
    form.clearErrors();
}
function chooseSchedule(schedule: Session) {
    selectedSchedule.value = schedule;
    selectedDate.value = null;
    selected.value = null;
    form.clearErrors();
    step.value = 3;
}
function chooseDate(date: string) {
    const session = dates.value.find((item) => item.session_date === date);
    if (!session || session.available_count < 1) return;
    selectedDate.value = date;
    selected.value = session;
    form.session_date = date;
    form.clearErrors();
    step.value = 5;
}
function goBack() {
    if (step.value === 5) {
        step.value = 3;
        return;
    }
    step.value = Math.max(1, step.value - 1);
}
function goNext() {
    if (!canContinue.value) return;
    step.value = Math.min(7, step.value + 1);
}
function submit() {
    if (!selected.value || selected.value.available_count < 1) return;
    form.session_date = selected.value.session_date;
    form.post(`/kelas/${selected.value.id}/register`, {
        preserveScroll: true,
        onSuccess: () => {
            selectedFacility.value = null;
            selectedClass.value = null;
            selectedSchedule.value = null;
            selectedDate.value = null;
            selected.value = null;
            step.value = 1;
            form.reset('guest_phone');
        },
        onError: (errors) => {
            const fields = Object.keys(errors);
            if (fields.includes('schedule')) step.value = 3;
            else if (fields.includes('payment_method')) step.value = 7;
            else if (fields.some((field) => ['guest_name', 'guest_email', 'guest_phone'].includes(field))) step.value = 5;
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
                selectedSchedule.value = session;
                selected.value = session;
                form.session_date = session.session_date;
                selectedDate.value = session.session_date;
                step.value = 5;
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
                                    <template v-else>{{ index + 1 }}</template>
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
                                <p class="mt-3 text-xs text-slate-500">{{ new Set(sportClass.schedules.map((schedule) => schedule.id)).size }} jadwal mingguan tersedia</p>
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
                                <h2 class="font-semibold">Pilih jadwal dan tanggal kelas</h2>
                                <p class="mt-1 text-xs text-slate-500">{{ selectedFacility?.name }} · {{ selectedClass?.name }}</p>
                            </div>
                            <span class="rounded-full bg-lime-50 px-3 py-1.5 text-xs font-semibold text-lime-800">{{ schedulesByDay.reduce((total, group) => total + group.schedules.length, 0) }} jadwal tersedia</span>
                        </div>
                        <div v-if="schedulesByDay.length" class="space-y-5">
                            <div v-for="group in schedulesByDay" :key="group.day">
                                <h3 class="mb-3 text-sm font-bold text-slate-700">{{ dayLabel(group.day) }}</h3>
                                <div class="space-y-3">
                                    <div v-for="schedule in group.schedules" :key="schedule.id" class="rounded-2xl border p-4 transition"
                                        :class="selectedSchedule?.id === schedule.id ? 'border-[#a4da01] bg-lime-50/40 ring-2 ring-[#a4da01]/30' : 'border-slate-200'">
                                        <button type="button" class="w-full text-left" @click="chooseSchedule(schedule)">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <span class="rounded-full bg-lime-50 px-2.5 py-1 text-[11px] font-bold text-lime-800">{{ schedule.start_time }}–{{ schedule.end_time }}</span>
                                                <span class="text-xs font-semibold text-slate-500">{{ schedule.trainer || 'Trainer akan diumumkan' }}</span>
                                            </div>
                                            <div class="mt-3 flex items-center justify-between gap-3">
                                                <h4 class="font-bold">{{ selectedClass?.name }}</h4>
                                                <span class="font-bold">{{ money(schedule.price) }}</span>
                                            </div>
                                            <p class="mt-1 text-xs text-lime-800">{{ selectedSchedule?.id === schedule.id ? 'Pilih salah satu tanggal di bawah' : 'Klik untuk melihat tanggal yang tersedia' }}</p>
                                        </button>
                                        <div v-if="selectedSchedule?.id === schedule.id" class="mt-4 border-t border-lime-200 pt-4">
                                            <p class="mb-3 text-sm font-semibold text-slate-700">Pilih tanggal untuk jadwal ini</p>
                                            <div v-if="dates.length" class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                                <button v-for="item in dates" :key="item.session_date" type="button" :disabled="item.available_count < 1"
                                                    class="rounded-xl border bg-white p-3 text-left transition hover:border-lime-300 hover:bg-lime-50/40 disabled:cursor-not-allowed disabled:opacity-50"
                                                    :class="selectedDate === item.session_date ? 'border-[#a4da01] bg-lime-50 ring-2 ring-[#a4da01]/30' : 'border-slate-200'"
                                                    @click="chooseDate(item.session_date)">
                                                    <span class="text-xs font-bold tracking-wider text-lime-800 uppercase">{{ dayLabel(item.day) }}</span>
                                                    <h3 class="mt-1 text-sm font-bold">{{ dateLabel(item.session_date) }}</h3>
                                                    <p class="mt-1 text-xs text-slate-500">{{ item.available_count }} tempat tersisa</p>
                                                    <span class="mt-2 inline-flex items-center text-xs font-semibold text-lime-800">Pilih tanggal <ArrowRight class="ml-1 size-3" /></span>
                                                </button>
                                            </div>
                                            <p v-else class="text-sm text-slate-500">Belum ada tanggal tersedia untuk jadwal ini.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-xl bg-slate-50 px-5 py-12 text-center">
                            <h3 class="font-semibold text-slate-800">Belum ada jadwal kelas</h3>
                            <p class="mt-1 text-sm text-slate-500">Silakan pilih kelas lain.</p>
                        </div>
                        <div class="mt-6 flex border-t border-slate-100 pt-5">
                            <button type="button" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700" @click="goBack"><ArrowLeft class="mr-2 size-4" /> Kembali</button>
                        </div>
                    </section>

                    <div v-else class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <section class="rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                            <div v-if="step === 5" class="space-y-5">
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

                            <div v-else-if="step === 6" class="space-y-5">
                                <div>
                                    <h2 class="font-semibold">Konfirmasi pendaftaran</h2>
                                    <p class="mt-1 text-sm text-slate-500">Pastikan semua detail kelas dan data peserta sudah benar sebelum memilih pembayaran.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Detail kelas</p>
                                    <div class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Olahraga</span><strong class="text-right">{{ selectedFacility?.name }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Kelas</span><strong class="text-right">{{ selectedClass?.name }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Tanggal</span><strong class="text-right">{{ selected ? dateLabel(selected.session_date) : '—' }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Waktu</span><strong class="text-right">{{ selected?.start_time }}–{{ selected?.end_time }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Instruktur</span><strong class="text-right">{{ selected?.trainer || 'Akan diumumkan' }}</strong></div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Data peserta</p>
                                    <div class="mt-3 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Nama</span><strong class="text-right">{{ form.guest_name || '—' }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Email</span><strong class="break-all text-right">{{ form.guest_email || '—' }}</strong></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-500">Nomor HP</span><strong class="text-right">{{ form.guest_phone || '—' }}</strong></div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-lime-200 bg-lime-50 p-4">
                                    <p class="text-sm font-semibold text-lime-900">Total pendaftaran</p>
                                    <p class="mt-1 text-2xl font-bold text-lime-900">{{ selected ? money(selected.price) : '—' }}</p>
                                    <p class="mt-1 text-xs text-lime-800">Harga untuk satu peserta. Metode pembayaran dipilih pada langkah berikutnya.</p>
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
                                <button v-if="step === 5" type="button" :disabled="!canContinue" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] disabled:opacity-40" @click="goNext">Lanjut konfirmasi <ArrowRight class="ml-2 size-4" /></button>
                                <button v-else-if="step === 6" type="button" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428]" @click="goNext">Lanjut ke pembayaran <ArrowRight class="ml-2 size-4" /></button>
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
                                <div v-if="step >= 6"><p class="text-slate-500">Peserta</p><p class="mt-1 font-semibold">{{ form.guest_name || 'Belum diisi' }}</p><p class="mt-0.5 text-slate-500">{{ form.guest_email || 'Email belum diisi' }}</p></div>
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
