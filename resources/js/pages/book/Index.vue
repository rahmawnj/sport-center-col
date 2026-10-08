<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    Check,
    Clock,
    LayoutGrid,
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

const props = defineProps<{ facilities: Facility[] }>();

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const step = ref(1);
const facility = ref<Facility | null>(null);
const pkg = ref<BookingPackage | null>(null);
const date = ref('');
const slots = ref<Slot[]>([]);
const loadingSlots = ref(false);
const slot = ref<Slot | null>(null);
const courtId = ref<number | null>(null);

const form = useForm({
    package_id: null as number | null,
    court_id: null as number | null,
    date: '',
    start_time: '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
});

const steps = [
    { id: 1, label: 'Fasilitas' },
    { id: 2, label: 'Paket' },
    { id: 3, label: 'Jadwal' },
    { id: 4, label: 'Lapangan' },
    { id: 5, label: 'Data diri' },
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

const visibleSteps = computed(() =>
    steps.filter((item) => isSession.value || (item.id !== 3 && item.id !== 4)),
);

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
            return !!date.value && !!slot.value;
        case 4:
            return !!courtId.value;
        default:
            return true;
    }
});

watch(date, () => {
    slot.value = null;
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
    slot.value = item;
    courtId.value = null;
}

function chooseCourt(id: number) {
    courtId.value = id;
}

function next() {
    if (step.value === 2 && !isSession.value) {
        step.value = 5;

        return;
    }

    if (step.value === 3 && slot.value) {
        form.package_id = pkg.value?.id ?? null;
        form.date = date.value;
        form.start_time = slot.value.start_time;
    }

    if (step.value === 4) {
        form.court_id = courtId.value;
    }

    step.value = Math.min(5, step.value + 1);
}

function back() {
    if (step.value === 5 && !isSession.value) {
        step.value = 2;

        return;
    }

    step.value = Math.max(1, step.value - 1);
}

function submit() {
    form.package_id = pkg.value?.id ?? null;

    if (isSession.value) {
        form.date = date.value;
        form.start_time = slot.value?.start_time ?? '';
        form.court_id = courtId.value;
    } else {
        form.date = '';
        form.start_time = '';
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
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 w-full max-w-5xl items-center justify-between px-4"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <LayoutGrid class="size-5 text-primary" />
                    Sport Center
                </Link>
                <Link
                    href="/"
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Beranda
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl px-4 py-8">
            <h1 class="text-2xl font-semibold tracking-tight">Booking</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Pilih fasilitas dan paket, lalu lengkapi data pemesanan.
            </p>

            <!-- Stepper -->
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <div
                    v-for="(item, index) in visibleSteps"
                    :key="item.id"
                    class="flex items-center gap-2"
                >
                    <div
                        class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm"
                        :class="
                            step === item.id
                                ? 'border-primary bg-primary/10 text-primary'
                                : step > item.id
                                  ? 'border-primary/40 text-primary'
                                  : 'text-muted-foreground'
                        "
                    >
                        <span
                            class="flex size-5 items-center justify-center rounded-full text-xs"
                            :class="
                                step > item.id
                                    ? 'bg-primary text-primary-foreground'
                                    : step === item.id
                                      ? 'bg-primary/20'
                                      : 'bg-muted'
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

            <div class="mt-6 rounded-2xl border bg-card p-4 shadow-sm md:p-6">
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
                            class="flex items-center gap-3 rounded-xl border bg-background p-4 text-left transition-colors hover:border-primary hover:bg-primary/5"
                            :class="
                                facility?.id === item.id
                                    ? 'border-primary bg-primary/5'
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
                            class="group relative flex flex-col gap-3 rounded-xl border bg-background p-4 text-left transition-all hover:border-primary hover:shadow-sm"
                            :class="
                                pkg?.id === item.id
                                    ? 'border-primary ring-2 ring-primary/30'
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
                                class="mt-auto flex items-end justify-between gap-2 border-t pt-3"
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

                <!-- Step 3: Date & Slot -->
                <section v-else-if="step === 3" class="space-y-4">
                    <h2 class="font-semibold">Pilih tanggal & jam</h2>
                    <div class="grid max-w-xs gap-2">
                        <Label for="booking-date">Tanggal</Label>
                        <Input
                            id="booking-date"
                            v-model="date"
                            type="date"
                            :min="today"
                        />
                    </div>

                    <div
                        v-if="loadingSlots"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Loader2 class="size-4 animate-spin" />
                        Memuat jadwal...
                    </div>

                    <template v-else-if="date">
                        <div
                            v-if="slots.length"
                            class="grid gap-2 sm:grid-cols-3 lg:grid-cols-4"
                        >
                            <button
                                v-for="item in slots"
                                :key="item.start_time"
                                type="button"
                                :disabled="item.courts.length === 0"
                                class="rounded-xl border p-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                :class="
                                    slot?.start_time === item.start_time
                                        ? 'border-primary bg-primary/5'
                                        : 'hover:border-primary hover:bg-primary/5'
                                "
                                @click="chooseSlot(item)"
                            >
                                <div class="font-medium">
                                    {{ item.start_time }}–{{ item.end_time }}
                                </div>
                                <div class="text-sm text-primary">
                                    {{ formatIDR(item.price) }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ item.courts.length }} lapangan tersedia
                                </div>
                            </button>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            Tidak ada jadwal tersedia pada tanggal ini.
                        </p>
                    </template>
                </section>

                <!-- Step 4: Court -->
                <section v-else-if="step === 4" class="space-y-4">
                    <h2 class="font-semibold">Pilih lapangan</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ slot?.start_time }}–{{ slot?.end_time }} ·
                        {{ slot && formatIDR(slot.price) }}
                    </p>
                    <div
                        v-if="slot?.courts.length"
                        class="grid gap-3 sm:grid-cols-3"
                    >
                        <button
                            v-for="item in slot.courts"
                            :key="item.id"
                            type="button"
                            class="flex items-center gap-3 rounded-xl border bg-background p-4 text-left transition-colors hover:border-primary hover:bg-primary/5"
                            :class="
                                courtId === item.id
                                    ? 'border-primary bg-primary/5'
                                    : ''
                            "
                            @click="chooseCourt(item.id)"
                        >
                            <MapPin class="size-4 text-primary" />
                            <span class="font-medium">{{ item.name }}</span>
                        </button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Tidak ada lapangan tersedia.
                    </p>
                </section>

                <!-- Step 5: Guest data -->
                <section v-else class="space-y-4">
                    <h2 class="font-semibold">Data diri</h2>

                    <div class="rounded-xl border bg-muted/40 p-4 text-sm">
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

                <div
                    class="mt-6 flex items-center justify-between border-t pt-4"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="step === 1"
                        @click="back"
                    >
                        <ArrowLeft class="mr-2 size-4" /> Kembali
                    </Button>

                    <Button
                        v-if="step < 5"
                        type="button"
                        :disabled="!canNext"
                        @click="next"
                    >
                        Lanjut <ArrowRight class="ml-2 size-4" />
                    </Button>
                    <Button
                        v-else
                        type="button"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 size-4 animate-spin"
                        />
                        Konfirmasi booking
                    </Button>
                </div>
            </div>
        </main>
    </div>
</template>
