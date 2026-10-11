<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    Check,
    Clock,
    Loader2,
    MapPin,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DayType = 'weekday' | 'weekend' | 'all';
type PricingRule = {
    day_type: DayType;
    start_time: string | null;
    end_time: string | null;
    price: number;
};
type BookingPackage = {
    id: number;
    name: string;
    type: 'session' | 'membership' | 'entry';
    duration_minutes: number;
    pricing_rules: PricingRule[];
};
type Facility = {
    id: number;
    name: string;
    slug: string;
    packages: BookingPackage[];
};
type Court = { id: number; name: string };
type Slot = {
    start_time: string;
    end_time: string;
    price: number;
    courts: Court[];
};

const props = defineProps<{ facilities: Facility[]; initialFacilityId: number | null; success?: string | null }>();

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const initialFacility =
    props.facilities.find((item) => item.id === props.initialFacilityId) ?? null;
const step = ref(initialFacility ? 2 : 1);
const facility = ref<Facility | null>(initialFacility);
const pkg = ref<BookingPackage | null>(null);
const date = ref('');
const slots = ref<Slot[]>([]);
const loadingSlots = ref(false);
const slot = ref<Slot | null>(null);
const selectedSlots = ref<string[]>([]);
const slotSelectionError = ref('');
const courtId = ref<number | null>(null);
const termsAccepted = ref(false);

const form = useForm({
    package_id: null as number | null,
    court_id: null as number | null,
    date: '',
    start_time: '',
    duration_hours: 1,
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    terms_accepted: false,
});

const steps = [
    { id: 1, label: 'Fasilitas' },
    { id: 2, label: 'Paket' },
    { id: 3, label: 'Jadwal & lapangan' },
    { id: 4, label: 'Personal information' },
    { id: 5, label: 'Order confirmation' },
    { id: 6, label: 'Payment & terms' },
];

const packageTypeLabel: Record<string, string> = {
    session: 'Sesi',
    membership: 'Keanggotaan',
    entry: 'Harian',
};

const isSession = computed(() => pkg.value?.type === 'session');

const packagePrice = computed(() => {
    if (!pkg.value?.pricing_rules.length) {
        return null;
    }

    return Math.min(...pkg.value.pricing_rules.map((rule) => rule.price));
});

const displayPrice = computed(() =>
    isSession.value ? (slot.value?.price ?? null) : packagePrice.value,
);

const visibleSteps = computed(() => {
    // Before a package is selected, show the complete flow so the stepper
    // does not prematurely jump from step 2 to step 5.
    if (!pkg.value) {
        return steps;
    }

    // Membership/entry packages skip the date and court selection steps.
    return steps.filter((item) => isSession.value || item.id !== 3);
});

function packagePriceLabel(item: BookingPackage) {
    if (!item.pricing_rules.length) {
        return '—';
    }

    const prices = item.pricing_rules.map((rule) => rule.price);

    return formatIDR(Math.min(...prices));
}

const canNext = computed(() => {
    switch (step.value) {
        case 1:
            return !!facility.value;
        case 2:
            return !!pkg.value;
        case 3:
            return !!date.value && !!slot.value && selectedSlots.value.length > 0 && !!courtId.value;
        case 4:
            return !!form.guest_name.trim() && !!form.guest_email.trim() && form.guest_email.includes('@') && form.guest_email.includes('.') && !!form.guest_phone.trim();
        case 5:
            return true;
        case 6:
            return termsAccepted.value;
        default:
            return false;
    }
});

watch(date, () => {
    slot.value = null;
    selectedSlots.value = [];
    slotSelectionError.value = '';
    courtId.value = null;
    slots.value = [];

    if (!pkg.value || !date.value) {
        return;
    }

    loadingSlots.value = true;
    fetch(`/book/availability?package_id=${pkg.value.id}&date=${date.value}`, {
        headers: { Accept: 'application/json' },
    })
        .then((response) => response.json())
        .then((data) => {
            slots.value = data.slots ?? [];
        })
        .finally(() => {
            loadingSlots.value = false;
        });
});

function chooseFacility(item: Facility) {
    facility.value = item;
    pkg.value = null;
    step.value = 2;
}

function choosePackage(item: BookingPackage) {
    pkg.value = item;
    step.value = item.type === 'session' ? 3 : 5;
}

function chooseSlot(item: Slot) {
    slotSelectionError.value = '';
    const currentlySelected = new Set(selectedSlots.value);
    const clickedIndex = slots.value.findIndex((entry) => entry.start_time === item.start_time);

    if (currentlySelected.has(item.start_time)) {
        // Keep the selection continuous when removing a checked hour.
        const selected = [...currentlySelected]
            .map((time) => slots.value.findIndex((entry) => entry.start_time === time))
            .filter((index) => index >= 0)
            .sort((a, b) => a - b);
        const first = selected[0];
        const last = selected[selected.length - 1];

        if (clickedIndex === first) {
            currentlySelected.delete(item.start_time);
        } else if (clickedIndex === last) {
            currentlySelected.delete(item.start_time);
        } else {
            // Removing a middle hour trims the booking at that point.
            selected.forEach((index) => {
                if (index >= clickedIndex) currentlySelected.delete(slots.value[index].start_time);
            });
        }
    } else {
        // Selecting a later/earlier hour automatically fills every hour in between.
        const selectedIndices = [...currentlySelected]
            .map((time) => slots.value.findIndex((entry) => entry.start_time === time))
            .filter((index) => index >= 0);
        const first = selectedIndices.length ? Math.min(...selectedIndices, clickedIndex) : clickedIndex;
        const last = selectedIndices.length ? Math.max(...selectedIndices, clickedIndex) : clickedIndex;

        for (let index = first; index <= last; index++) {
            currentlySelected.add(slots.value[index].start_time);
        }
    }

    const chosen = [...currentlySelected]
        .map((time) => slots.value.find((entry) => entry.start_time === time))
        .filter((entry): entry is Slot => !!entry)
        .sort((a, b) => a.start_time.localeCompare(b.start_time));

    selectedSlots.value = chosen.map((entry) => entry.start_time);
    courtId.value = null;

    if (!chosen.length) {
        slot.value = null;
        return;
    }

    const commonCourts = chosen[0].courts.filter((court) =>
        chosen.every((entry) => entry.courts.some((available) => available.id === court.id)),
    );

    slot.value = {
        start_time: chosen[0].start_time,
        end_time: chosen[chosen.length - 1].end_time,
        price: chosen.reduce((total, entry) => total + entry.price, 0),
        courts: commonCourts,
    };
}

function chooseCourt(id: number) {
    courtId.value = id;
}

function next() {
    if (step.value === 2 && !isSession.value) {
        step.value = 4;
        return;
    }
    if (step.value === 3 && slot.value) {
        form.package_id = pkg.value?.id ?? null;
        form.date = date.value;
        form.start_time = slot.value.start_time;
        form.duration_hours = selectedSlots.value.length;
        form.court_id = courtId.value;
    }
    step.value = Math.min(6, step.value + 1);
}

function back() {
    if (step.value === 4 && !isSession.value) {
        step.value = 2;
        return;
    }
    step.value = Math.max(1, step.value - 1);
}

function submit() {
    form.terms_accepted = termsAccepted.value;
    form.package_id = pkg.value?.id ?? null;

    if (isSession.value) {
        form.date = date.value;
        form.start_time = slot.value?.start_time ?? '';
        form.duration_hours = selectedSlots.value.length || 1;
        form.court_id = courtId.value;
    } else {
        form.date = '';
        form.start_time = '';
        form.duration_hours = 1;
        form.court_id = null;
    }

    form.post('/book', {
        preserveScroll: true,
    });
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDuration(minutes: number) {
    if (minutes % 60 === 0) {
        return `${minutes / 60} jam`;
    }

    return `${minutes} menit`;
}
</script>

<template>
    <Head title="Booking" />
    <PublicLayout>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <main class="mx-auto w-full max-w-5xl px-4 py-8">
            <section v-if="props.success" class="flex min-h-[75vh] items-center justify-center py-10">
                <div class="w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-[0_20px_80px_rgba(15,23,42,0.12)]">
                    <div class="relative overflow-hidden bg-[#001428] px-6 py-12 text-center text-white sm:px-12 sm:py-16">
                        <div class="pointer-events-none absolute -right-12 -top-16 size-56 rounded-full border-[30px] border-white/5"></div>
                        <div class="pointer-events-none absolute -bottom-24 -left-10 size-64 rounded-full border-[36px] border-white/5"></div>
                        <div class="relative mx-auto flex size-24 items-center justify-center rounded-full bg-[#a4da01] text-[#001428] shadow-lg shadow-black/20 sm:size-28">
                            <Check class="size-12 sm:size-14" :stroke-width="3" />
                        </div>
                        <p class="relative mt-7 text-sm font-semibold uppercase tracking-[0.22em] text-[#c9f36a]">Konfirmasi berhasil</p>
                        <h1 class="relative mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Booking berhasil!</h1>
                        <p class="relative mx-auto mt-4 max-w-xl text-base leading-7 text-slate-200 sm:text-lg">Jadwal booking kamu sudah tersimpan. Terima kasih sudah memilih fasilitas kami.</p>
                    </div>
                    <div class="px-6 py-8 sm:px-12 sm:py-10">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5 sm:p-6">
                            <div class="flex items-start gap-3">
                                <Check class="mt-0.5 size-5 shrink-0 text-emerald-700" />
                                <div>
                                    <p class="font-semibold text-emerald-950">Booking tercatat di sistem</p>
                                    <p class="mt-1 text-sm leading-6 text-emerald-900">{{ props.success }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-7 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="mb-2 flex size-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm"><Check class="size-5" /></div>
                                <p class="font-semibold text-slate-900">Data tersimpan</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">Informasi booking sudah masuk ke sistem.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="mb-2 flex size-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm"><Clock class="size-5" /></div>
                                <p class="font-semibold text-slate-900">Menunggu konfirmasi</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">Status booking dapat ditindaklanjuti oleh pengelola.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="mb-2 flex size-10 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm"><Check class="size-5" /></div>
                                <p class="font-semibold text-slate-900">Simpan kontak</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">Pastikan nomor HP dan email yang kamu isi aktif.</p>
                            </div>
                        </div>
                        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                            <a href="/" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke beranda</a>
                            <a href="/book" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#a4da01] px-6 py-3 text-sm font-bold text-[#001428] transition hover:bg-[#b6ed19]">Booking fasilitas lain <ArrowRight class="ml-2 size-4" /></a>
                        </div>
                    </div>
                </div>
            </section>
            <template v-else>
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Booking olahraga</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pilih fasilitas, tentukan jadwal, lalu konfirmasi dengan data kontakmu.
                    </p>
                </div>
            </div>
            <!-- Stepper -->
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <div
                    v-for="(item, index) in visibleSteps"
                    :key="item.id"
                    class="flex items-center gap-2"
                >
                    <div
                        class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition-colors"
                        :class="
                            step === item.id
                                ? 'bg-[#a4da01] text-[#001428]'
                                : step > item.id
                                  ? 'bg-slate-200 text-slate-700'
                                  : 'bg-white text-slate-400'
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
                            <Check v-if="step > item.id" class="size-3" />
                            <template v-else>{{ item.id }}</template>
                        </span>
                        {{ item.label }}
                    </div>
                    <ArrowRight
                        v-if="index < visibleSteps.length - 1"
                        class="size-4 text-muted-foreground/50"
                    />
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-white p-4 shadow-[0_8px_30px_rgba(15,23,42,0.06)] md:p-6">
                <!-- Step 1: Facility -->
                <section v-if="step === 1" class="space-y-4">
                    <h2 class="font-semibold">Pilih fasilitas</h2>
                    <div
                        v-if="props.facilities.length"
                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <button
                            v-for="item in props.facilities"
                            :key="item.id"
                            type="button"
                            class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-left transition-all hover:bg-lime-50 hover:shadow-sm"
                            :class="
                                facility?.id === item.id
                                    ? 'bg-lime-100 ring-2 ring-inset ring-[#a4da01]'
                                    : ''
                            "
                            @click="chooseFacility(item)"
                        >
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Building2 class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ item.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ item.packages.length }} paket
                                </div>
                            </div>
                        </button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Belum ada fasilitas yang bisa dibooking online.
                    </p>
                </section>

                <!-- Step 2: Package -->
                <section v-else-if="step === 2" class="space-y-4">
                    <h2 class="font-semibold">
                        Pilih paket · {{ facility?.name }}
                    </h2>
                    <div
                        v-if="facility?.packages.length"
                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <button
                            v-for="item in facility?.packages"
                            :key="item.id"
                            type="button"
                            class="group relative flex flex-col gap-3 rounded-xl bg-slate-50 p-4 text-left transition-all hover:bg-lime-50 hover:shadow-sm"
                            :class="
                                pkg?.id === item.id
                                    ? 'bg-lime-100 ring-2 ring-inset ring-[#a4da01]'
                                    : ''
                            "
                            @click="choosePackage(item)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <Clock class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">
                                            {{ item.name }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                item.duration_minutes > 0
                                                    ? formatDuration(
                                                          item.duration_minutes,
                                                      )
                                                    : 'Tanpa durasi'
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                >
                                    {{ packageTypeLabel[item.type] }}
                                </span>
                            </div>

                            <div
                                class="mt-auto flex items-end justify-between gap-2 pt-3"
                            >
                                <div>
                                    <div class="text-xs text-muted-foreground">
                                        Mulai dari
                                    </div>
                                    <div class="text-lg font-semibold">
                                        {{ packagePriceLabel(item) }}
                                    </div>
                                </div>
                                <span
                                    v-if="pkg?.id === item.id"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-primary"
                                >
                                    <Check class="size-4" /> Dipilih
                                </span>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground transition-colors group-hover:text-primary"
                                >
                                    Pilih
                                </span>
                            </div>
                        </button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Fasilitas ini belum memiliki paket.
                    </p>
                </section>

                <!-- Step 3: Date, slot, court and live summary -->
                <section v-else-if="step === 3" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.9fr)]">
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold">Pilih tanggal, jam & lapangan</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih jam yang tersedia, lalu pilih lapangan di bawahnya.</p>
                        </div>
                        <div class="grid max-w-sm gap-2">
                            <Label for="booking-date" class="text-sm font-semibold text-slate-700">Tanggal booking</Label>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <Input id="booking-date" v-model="date" type="date" :min="today" class="h-12 rounded-xl border-0 bg-white px-4 text-base font-semibold text-slate-800 shadow-sm ring-1 ring-slate-100 focus-visible:ring-2 focus-visible:ring-[#a4da01] focus-visible:ring-offset-0" />
                                <p class="mt-2 text-xs text-slate-500">Pilih tanggal untuk melihat jam yang tersedia.</p>
                            </div>
                        </div>
                        <div v-if="loadingSlots" class="flex items-center gap-2 text-sm text-muted-foreground"><Loader2 class="size-4 animate-spin" /> Memuat jadwal...</div>
                        <template v-else-if="date">
                            <div v-if="slots.length" class="space-y-3">
                                <p class="text-sm text-slate-600">Centang jam awal dan akhir yang diinginkan. Jam di antaranya otomatis ikut dipilih.</p>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    <label v-for="item in slots" :key="item.start_time" class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3 transition-colors has-[:disabled]:cursor-not-allowed has-[:checked]:bg-lime-100 hover:bg-lime-50" :class="selectedSlots.includes(item.start_time) ? 'ring-2 ring-inset ring-[#a4da01]' : ''">
                                        <input type="checkbox" :checked="selectedSlots.includes(item.start_time)" :disabled="item.courts.length === 0" class="mt-1 size-4 accent-[#8dbb00] disabled:cursor-not-allowed" @change="chooseSlot(item)" />
                                        <span class="min-w-0">
                                            <span class="block font-semibold text-slate-800">{{ item.start_time }}–{{ item.end_time }}</span>
                                            <span class="block text-sm font-medium text-slate-700">{{ formatIDR(item.price) }}</span>
                                            <span class="block text-xs text-slate-500">{{ item.courts.length }} lapangan tersedia</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Tidak ada jadwal tersedia pada tanggal ini.</p>
                        </template>
                        <div class="space-y-3 border-t border-slate-100 pt-5">
                            <div>
                                <h3 class="font-semibold">Pilih lapangan</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ slot ? slot.start_time + '–' + slot.end_time : 'Pilih jam terlebih dahulu.' }}</p>
                            </div>
                            <div v-if="slot?.courts.length" class="grid gap-3 sm:grid-cols-2">
                                <button v-for="item in slot.courts" :key="item.id" type="button" class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-left transition-colors hover:bg-lime-50" :class="courtId === item.id ? 'bg-lime-100 ring-2 ring-inset ring-[#a4da01]' : ''" @click="chooseCourt(item.id)">
                                    <MapPin class="size-4 text-primary" /><span class="font-medium">{{ item.name }}</span><Check v-if="courtId === item.id" class="ml-auto size-4 text-primary" />
                                </button>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Pilih tanggal dan jam untuk melihat lapangan yang tersedia.</p>
                        </div>
                    </div>
                    <aside class="h-fit rounded-2xl border border-slate-200 bg-slate-50 p-5 lg:sticky lg:top-24">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Booking detail</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-900">Ringkasan pesanan</h3>
                        <div class="mt-5 space-y-4 text-sm">
                            <div><p class="text-slate-500">Fasilitas</p><p class="mt-1 font-semibold">{{ facility?.name || 'Belum dipilih' }}</p></div>
                            <div><p class="text-slate-500">Paket</p><p class="mt-1 font-semibold">{{ pkg?.name || 'Belum dipilih' }}</p></div>
                            <div><p class="text-slate-500">Tanggal</p><p class="mt-1 font-semibold">{{ date || 'Belum dipilih' }}</p></div>
                            <div><p class="text-slate-500">Jam</p><p class="mt-1 font-semibold">{{ slot ? slot.start_time + '–' + slot.end_time : 'Belum dipilih' }}</p></div>
                            <div><p class="text-slate-500">Lapangan</p><p class="mt-1 font-semibold">{{ slot?.courts.find((court) => court.id === courtId)?.name || 'Belum dipilih' }}</p></div>
                            <div class="border-t border-slate-200 pt-4"><p class="text-slate-500">Total</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ slot ? formatIDR(slot.price) : '—' }}</p></div>
                        </div>
                    </aside>
                </section>

                <!-- Step 4: Personal information -->
                <section v-else-if="step === 4" class="space-y-4">
                    <h2 class="text-lg font-semibold">Personal information</h2>
                    <p class="text-sm text-slate-500">Masukkan detail kontak untuk konfirmasi booking.</p>

                    <div class="rounded-xl bg-slate-50 p-4 text-sm">
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Fasilitas</span>
                            <span class="font-medium">{{
                                facility?.name
                            }}</span>
                        </div>
                        <div class="mt-1 flex justify-between gap-2">
                            <span class="text-muted-foreground">Paket</span>
                            <span class="font-medium">{{ pkg?.name }}</span>
                        </div>
                        <div
                            v-if="isSession"
                            class="mt-1 flex justify-between gap-2"
                        >
                            <span class="text-muted-foreground">Jadwal</span>
                            <span class="font-medium">
                                {{ date }} · {{ slot?.start_time }}–{{
                                    slot?.end_time
                                }}
                            </span>
                        </div>
                        <div
                            class="mt-2 flex justify-between gap-2 border-t pt-2"
                        >
                            <span class="text-muted-foreground">Total</span>
                            <span class="font-semibold text-primary">
                                {{
                                    displayPrice !== null
                                        ? formatIDR(displayPrice)
                                        : '—'
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="guest-name">Nama</Label>
                            <Input
                                id="guest-name"
                                v-model="form.guest_name"
                                placeholder="Nama lengkap"
                            />
                            <p
                                v-if="form.errors.guest_name"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.guest_name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="guest-email">Email</Label>
                            <Input
                                id="guest-email"
                                v-model="form.guest_email"
                                type="email"
                                placeholder="email@contoh.com"
                            />
                            <p
                                v-if="form.errors.guest_email"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.guest_email }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="guest-phone">No. HP</Label>
                            <Input
                                id="guest-phone"
                                v-model="form.guest_phone"
                                placeholder="08xxxxxxxxxx"
                            />
                            <p
                                v-if="form.errors.guest_phone"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.guest_phone }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="form.errors.start_time"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.start_time }}
                    </p>
                </section>

                <!-- Step 5: Order confirmation -->
                <section v-else-if="step === 5" class="space-y-5">
                    <div><h2 class="text-xl font-bold">Order confirmation</h2><p class="mt-1 text-sm text-slate-500">Periksa kembali detail pesanan dan informasi pemesan.</p></div>
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <div class="border-b border-slate-100 pb-4"><p class="font-semibold">{{ facility?.name }}</p><p class="mt-1 text-sm text-slate-500">{{ pkg?.name }}</p></div>
                        <div class="grid gap-4 py-4 sm:grid-cols-2">
                            <div><p class="text-sm text-slate-500">Nama pemesan</p><p class="mt-1 font-semibold">{{ form.guest_name }}</p></div>
                            <div><p class="text-sm text-slate-500">Email</p><p class="mt-1 font-semibold break-all">{{ form.guest_email }}</p></div>
                            <div><p class="text-sm text-slate-500">Nomor HP</p><p class="mt-1 font-semibold">{{ form.guest_phone }}</p></div>
                            <div v-if="isSession"><p class="text-sm text-slate-500">Tanggal & waktu</p><p class="mt-1 font-semibold">{{ date }} · {{ slot?.start_time }}–{{ slot?.end_time }}</p></div>
                            <div v-if="isSession"><p class="text-sm text-slate-500">Lapangan</p><p class="mt-1 font-semibold">{{ slot?.courts.find((court) => court.id === courtId)?.name || '—' }}</p></div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-4"><div><p class="font-semibold">Total pesanan</p><p class="mt-1 text-xs text-slate-500">Sesuai paket dan jadwal yang dipilih</p></div><p class="text-2xl font-bold">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</p></div>
                    </div>
                </section>

                <!-- Step 6: Payment and terms -->
                <section v-else class="space-y-5">
                    <div><h2 class="text-xl font-bold">Payment & terms</h2><p class="mt-1 text-sm text-slate-500">Konfirmasi booking dan persetujuan syarat dan ketentuan.</p></div>
                    <div class="rounded-2xl bg-[#001428] p-6 text-white">
                        <div class="flex items-center gap-3"><div class="flex size-12 items-center justify-center rounded-full bg-[#a4da01] text-[#001428]"><Check class="size-6" :stroke-width="3" /></div><div><p class="text-lg font-bold">Konfirmasi booking</p><p class="mt-1 text-sm text-slate-300">Total pesanan</p></div></div>
                        <p class="mt-5 text-3xl font-bold text-[#c9f36a]">{{ displayPrice !== null ? formatIDR(displayPrice) : '—' }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Konfirmasi ini menyimpan booking dan belum memproses pembayaran online. Detail pembayaran dapat dikonfirmasi dengan pengelola.</p>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50" :class="termsAccepted ? 'border-[#a4da01] bg-lime-50' : ''">
                        <input v-model="termsAccepted" type="checkbox" class="mt-1 size-4 accent-[#8dbb00]" />
                        <span class="text-sm leading-6 text-slate-700">Saya telah memeriksa detail pesanan dan menyetujui syarat serta ketentuan booking fasilitas.</span>
                    </label>
                    <p v-if="!termsAccepted" class="text-xs text-slate-500">Centang persetujuan untuk mengaktifkan tombol konfirmasi.</p>
                    <p v-if="form.errors.start_time || form.errors.court_id || form.errors.package_id" class="text-sm text-destructive">{{ form.errors.start_time || form.errors.court_id || form.errors.package_id }}</p>
                </section>

                <div
                    class="mt-6 flex items-center justify-between pt-4"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="step === 1"
                        @click="back"
                    >
                        <ArrowLeft class="mr-2 size-4" /> Kembali
                    </Button>

                    <Button v-if="step < 6" type="button" :disabled="!canNext" @click="next">
                        {{ step === 4 ? 'Lanjut ke order confirmation' : step === 5 ? 'Lanjut ke payment' : 'Lanjut' }} <ArrowRight class="ml-2 size-4" />
                    </Button>
                    <Button v-else type="button" :disabled="!canNext || form.processing" @click="submit">
                        <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                        {{ form.processing ? 'Mengonfirmasi booking...' : 'Setujui & konfirmasi booking' }} <Check v-if="!form.processing" class="ml-2 size-4" />
                    </Button>
                </div>
            </div>
        </template>
        </main>
    </div>
    </PublicLayout>
</template>
