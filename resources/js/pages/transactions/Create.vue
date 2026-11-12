<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    Clock,
    Loader2,
    MapPin,
    Minus,
    Package as PackageIcon,
    Plus,
    ShoppingCart,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PricingRule = { id: number; label: string; price: number };
type SellPackage = {
    id: number;
    name: string;
    type: 'session' | 'membership' | 'entry';
    duration_minutes: number;
    duration_days: number | null;
    price: number | null;
    pricing_rules: PricingRule[];
};
type Facility = {
    id: number;
    name: string;
    has_booking: boolean;
    has_session: boolean;
    has_membership: boolean;
    packages: SellPackage[];
};
type Member = { id: number; name: string };
type CashierClassSchedule = { id: number; class_name: string; facility_id: number; day: string; start_time: string; end_time: string; trainer: string | null; capacity: number; registered_count: number; price: number };
type Slot = {
    start_time: string;
    end_time: string;
    price: number;
    courts: { id: number; name: string }[];
};
type ExistingBooking = {
    court_id: number;
    court_name: string | null;
    start_time: string;
    end_time: string;
    status: string;
    customer_name: string | null;
};

const props = withDefaults(defineProps<{ facilities: Facility[]; members: Member[]; classSchedules?: CashierClassSchedule[] }>(), { classSchedules: () => [] });

const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const clock = ref(new Date());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => {
        clock.value = new Date();
    }, 30_000);
});

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});

function isSlotPast(slot: Slot) {
    if (date.value !== today) {
        return false;
    }

    const [hours, minutes] = slot.start_time.split(':').map(Number);

    return (
        hours * 60 + minutes <=
        clock.value.getHours() * 60 + clock.value.getMinutes()
    );
}

const facilityId = ref<number | null>(props.facilities[0]?.id ?? null);
const selected = ref<SellPackage | null>(null);
const ruleId = ref<string>('');
const quantity = ref(1);
const memberId = ref<string>('');
const activeService = ref<'booking' | 'session' | 'membership'>(props.facilities[0]?.has_booking ? 'booking' : props.facilities[0]?.has_session ? 'session' : 'membership');
const sessionMode = ref<'entry' | 'class'>('entry');
const classDate = ref('');
const classScheduleId = ref<number | null>(null);
const participantNamesText = ref('');

const date = ref('');
const slots = ref<Slot[]>([]);
const existingBookings = ref<ExistingBooking[]>([]);
const loadingSlots = ref(false);
const selectedSlot = ref<Slot | null>(null);
const courtId = ref<number | null>(null);

const form = useForm({
    package_id: null as number | null,
    pricing_rule_id: null as number | null,
    quantity: 1,
    customer_name: '',
    payment_method: 'cash',
    payment_status: 'paid',
    amount: 0,
    court_id: null as number | null,
    date: '',
    start_time: '',
    user_id: null as number | null,
    class_schedule_id: null as number | null,
    participants: [] as string[],
});

const methodOptions = [
    { value: 'cash', label: 'Tunai' },
    { value: 'bank_transfer', label: 'Transfer Bank' },
    { value: 'qris', label: 'QRIS' },
];
const statusOptions = [
    { value: 'paid', label: 'Lunas' },
    { value: 'pending', label: 'Belum bayar (pending)' },
];
const packageTypeLabel: Record<string, string> = {
    session: 'Sesi',
    membership: 'Keanggotaan',
    entry: 'Harian',
};

const activeFacility = computed(
    () => props.facilities.find((item) => item.id === facilityId.value) ?? null,
);
const activeServices = computed(() => {
    if (!activeFacility.value) return [];
    return [
        ...(activeFacility.value.has_booking ? [{ value: 'booking' as const, label: 'Booking' }] : []),
        ...(activeFacility.value.has_session ? [{ value: 'session' as const, label: 'Sesi' }] : []),
        ...(activeFacility.value.has_membership ? [{ value: 'membership' as const, label: 'Membership' }] : []),
    ];
});
const visiblePackages = computed(() => {
    const type = activeService.value === 'booking' ? 'session' : activeService.value === 'membership' ? 'membership' : 'entry';
    return activeFacility.value?.packages.filter((item) => item.type === type) ?? [];
});
const participantNames = computed(() => participantNamesText.value.split(/\r?\n/).map((name) => name.trim()).filter(Boolean));
const visibleClassSchedules = computed(() => {
    if (!activeFacility.value || !classDate.value) return [];
    const day = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(new Date(`${classDate.value}T12:00:00`)).toLowerCase();
    return props.classSchedules.filter((item) => item.facility_id === activeFacility.value?.id && item.day === day);
});
const selectedClassSchedule = computed(() => props.classSchedules.find((item) => item.id === classScheduleId.value) ?? null);
const activeRule = computed(
    () =>
        selected.value?.pricing_rules.find(
            (rule) => String(rule.id) === ruleId.value,
        ) ?? null,
);
const isSession = computed(() => selected.value?.type === 'session');
const isMembership = computed(() => selected.value?.type === 'membership');
const memberOptions = computed(() =>
    props.members.map((member) => ({
        value: String(member.id),
        label: member.name,
    })),
);
const membershipPeriod = computed(() => {
    if (!isMembership.value || !selected.value) {
        return null;
    }

    const days = selected.value.duration_days ?? 30;
    const start = new Date();
    const end = new Date();
    end.setDate(end.getDate() + days);

    return { start: formatDate(start), end: formatDate(end) };
});
const unitPrice = computed(() => {
    if (activeService.value === 'session' && sessionMode.value === 'class') return selectedClassSchedule.value?.price ?? 0;
    if (isSession.value) return selectedSlot.value?.price ?? 0;
    if (isMembership.value) return selected.value?.price ?? 0;
    return activeRule.value?.price ?? 0;
});
const total = computed(() => activeService.value === 'session' && sessionMode.value === 'class'
    ? unitPrice.value * participantNames.value.length
    : isSession.value ? unitPrice.value : unitPrice.value * quantity.value,
);
const canSubmit = computed(() => {
    if (activeService.value === 'session' && sessionMode.value === 'class') return !!classScheduleId.value && !!classDate.value && participantNames.value.length > 0;
    if (!selected.value) return false;

    if (isSession.value) {
        return !!selectedSlot.value && !!courtId.value;
    }

    if (isMembership.value) {
        return !!memberId.value;
    }

    return true;
});

watch(facilityId, () => {
    const facility = activeFacility.value;
    activeService.value = facility?.has_booking ? 'booking' : facility?.has_session ? 'session' : 'membership';
    selected.value = null;
    sessionMode.value = 'entry';
    classScheduleId.value = null;
    classDate.value = '';
    participantNamesText.value = '';
    ruleId.value = '';
    quantity.value = 1;
    memberId.value = '';
});

watch([unitPrice, quantity], () => {
    form.amount = total.value;
});

watch(activeService, () => {
    selected.value = null;
    ruleId.value = '';
    quantity.value = 1;
    memberId.value = '';
    classScheduleId.value = null;
    classDate.value = '';
    participantNamesText.value = '';
    sessionMode.value = 'entry';
});

watch(sessionMode, () => {
    selected.value = null;
    classScheduleId.value = null;
    classDate.value = '';
    participantNamesText.value = '';
});

watch(selected, () => {
    date.value = '';
    slots.value = [];
    existingBookings.value = [];
    selectedSlot.value = null;
    courtId.value = null;
    quantity.value = 1;
    memberId.value = '';
});

watch(memberId, (value) => {
    const member = props.members.find((item) => String(item.id) === value);

    if (member) {
        form.customer_name = member.name;
    }
});

watch(date, () => {
    selectedSlot.value = null;
    courtId.value = null;
    slots.value = [];
    existingBookings.value = [];

    if (!isSession.value || !selected.value || !date.value) {
        return;
    }

    loadingSlots.value = true;
    fetch(
        `/book/availability?package_id=${selected.value.id}&date=${date.value}`,
        { headers: { Accept: 'application/json' } },
    )
        .then((response) => response.json())
        .then((data) => {
            slots.value = data.slots ?? [];
            existingBookings.value = data.bookings ?? [];
        })
        .finally(() => {
            loadingSlots.value = false;
        });
});

function choosePackage(item: SellPackage) {
    selected.value = item;
    ruleId.value = item.type === 'membership' ? '' : String(item.pricing_rules[0]?.id ?? '');
}

function chooseSlot(slot: Slot) {
    selectedSlot.value = slot;
    courtId.value = null;
}

function chooseCourt(id: number) {
    courtId.value = id;
}

function changeQty(delta: number) {
    quantity.value = Math.max(1, quantity.value + delta);
}

function submit() {
    if (activeService.value === 'session' && sessionMode.value === 'class') {
        form.package_id = null;
        form.class_schedule_id = classScheduleId.value;
        form.participants = participantNames.value;
        form.date = classDate.value;
        form.quantity = participantNames.value.length;
        form.amount = total.value;
        form.pricing_rule_id = null;
        form.court_id = null;
        form.start_time = '';
        form.user_id = null;
        form.post('/transactions');
        return;
    }
    if (!selected.value) return;
    form.class_schedule_id = null;
    form.participants = [];
    form.package_id = selected.value.id;
    form.pricing_rule_id = !isSession.value && !isMembership.value && ruleId.value
        ? Number(ruleId.value)
        : null;
    form.quantity = isSession.value ? 1 : quantity.value;
    form.court_id = isSession.value ? courtId.value : null;
    form.date = isSession.value ? date.value : '';
    form.start_time = isSession.value
        ? (selectedSlot.value?.start_time ?? '')
        : '';
    form.user_id =
        isMembership.value && memberId.value ? Number(memberId.value) : null;
    form.post('/transactions');
}

function packageMinPrice(item: SellPackage) {
    if (item.type === 'membership') return item.price === null ? '—' : formatIDR(item.price);
    if (!item.pricing_rules.length) return '—';
    return formatIDR(Math.min(...item.pricing_rules.map((rule) => rule.price)));
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDate(value: Date) {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(value);
}
</script>

<template>
    <Head title="Kasir" />
    <div class="space-y-6 p-4 md:p-6">
        <Link
            href="/transactions"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke transaksi
        </Link>

        <PageHeader
            eyebrow="Transaksi OTS"
            title="Kasir"
            description="Catat penjualan langsung (on the spot) di counter."
        />

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Catalog + schedule -->
            <div class="space-y-4 lg:col-span-2">
                <section class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="item in props.facilities"
                            :key="item.id"
                            type="button"
                            class="rounded-lg border px-3 py-2 text-sm font-medium transition-all"
                            :class="facilityId === item.id ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'"
                            @click="facilityId = item.id"
                        >
                            {{ item.name }}
                        </button>
                    </div>
                    <div v-if="activeServices.length" class="flex flex-wrap gap-2">
                        <button v-for="service in activeServices" :key="service.value" type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all"
                            :class="activeService === service.value ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:border-primary'"
                            @click="activeService = service.value">{{ service.label }}</button>
                    </div>
                </section>

                <div v-if="activeService === 'session'" class="flex gap-2">
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-medium" :class="sessionMode === 'entry' ? 'border-primary bg-primary text-primary-foreground' : 'bg-card'" @click="sessionMode = 'entry'">Tiket masuk</button>
                    <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-medium" :class="sessionMode === 'class' ? 'border-primary bg-primary text-primary-foreground' : 'bg-card'" @click="sessionMode = 'class'">Kelas terjadwal</button>
                </div>

                <div
                    v-if="visiblePackages.length && !(activeService === 'session' && sessionMode === 'class')"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <button
                        v-for="item in visiblePackages"
                        :key="item.id"
                        type="button"
                        class="flex items-start gap-3 rounded-xl border bg-card p-4 text-left transition-all hover:border-primary hover:shadow-sm"
                        :class="
                            selected?.id === item.id
                                ? 'border-primary ring-2 ring-primary/30'
                                : ''
                        "
                        @click="choosePackage(item)"
                    >
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <PackageIcon class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span class="truncate font-medium">
                                    {{ item.name }}
                                </span>
                                <span
                                    v-if="selected?.id === item.id"
                                    class="inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                >
                                    <Check class="size-3" />
                                </span>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ packageTypeLabel[item.type] }} ·
                                {{ item.duration_minutes }} menit
                            </div>
                            <div class="mt-1.5 text-sm font-semibold">
                                {{ packageMinPrice(item) }}
                            </div>
                        </div>
                    </button>
                </div>
                <p v-else-if="!(activeService === 'session' && sessionMode === 'class')" class="text-sm text-muted-foreground">
                    Belum ada paket untuk layanan ini.
                </p>

                <div v-if="activeService === 'session' && sessionMode === 'class'" class="space-y-4 rounded-2xl border bg-card p-4">
                    <div class="grid max-w-xs gap-2">
                        <Label for="class-date">Tanggal kelas</Label>
                        <Input id="class-date" v-model="classDate" type="date" :min="today" />
                    </div>
                    <div v-if="classDate" class="grid gap-2 sm:grid-cols-2">
                        <button v-for="item in visibleClassSchedules" :key="item.id" type="button"
                            class="rounded-xl border p-3 text-left transition-colors hover:border-primary"
                            :class="classScheduleId === item.id ? 'border-primary bg-primary/5 ring-1 ring-primary' : ''"
                            @click="classScheduleId = item.id">
                            <div class="font-semibold">{{ item.class_name }}</div>
                            <div class="text-xs text-muted-foreground">{{ item.start_time }}–{{ item.end_time }} · {{ item.trainer || 'Tanpa trainer' }}</div>
                            <div class="mt-1 text-sm font-semibold">{{ formatIDR(item.price) }}</div>
                            <div class="text-xs text-muted-foreground">Kapasitas {{ item.capacity }} peserta</div>
                        </button>
                    </div>
                    <p v-if="classDate && !visibleClassSchedules.length" class="text-sm text-muted-foreground">Tidak ada kelas tersedia pada tanggal ini.</p>
                    <div v-if="classScheduleId" class="grid gap-2">
                        <Label for="class-participants">Peserta (satu nama per baris, ketik Umum jika tanpa nama)</Label>
                        <textarea id="class-participants" v-model="participantNamesText" rows="4" class="w-full rounded-lg border bg-background px-3 py-2 text-sm" placeholder="Umum&#10;Nama peserta 2"></textarea>
                    </div>
                </div>

                <!-- Schedule for session packages -->
                <div
                    v-if="selected && isSession"
                    class="space-y-4 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <Clock class="size-4 text-primary" />
                        <h2 class="font-semibold">Jadwal & lapangan</h2>
                    </div>

                    <div class="grid max-w-xs gap-2">
                        <Label for="pos-date">Tanggal</Label>
                        <Input
                            id="pos-date"
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
                                :disabled="
                                    item.courts.length === 0 || isSlotPast(item)
                                "
                                class="rounded-xl border p-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                :class="
                                    selectedSlot?.start_time === item.start_time
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
                                    {{ item.courts.length }} lapangan
                                </div>
                            </button>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            Tidak ada jadwal tersedia pada tanggal ini.
                        </p>

                        <div
                            v-if="selectedSlot"
                            class="space-y-3 rounded-xl border-2 p-4 transition-colors"
                            :class="
                                courtId === null
                                    ? 'border-primary/60 bg-primary/5'
                                    : 'border-primary/30 bg-card'
                            "
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <MapPin class="size-4" />
                                    </div>
                                    <span class="font-semibold"
                                        >Pilih lapangan</span
                                    >
                                    <span
                                        v-if="courtId === null"
                                        class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold text-amber-600"
                                    >
                                        Wajib dipilih
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">
                                    {{ selectedSlot.courts.length }} tersedia
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button
                                    v-for="court in selectedSlot.courts"
                                    :key="court.id"
                                    type="button"
                                    class="flex items-center justify-between gap-2 rounded-lg border-2 px-3 py-2.5 text-sm font-medium transition-all"
                                    :class="
                                        courtId === court.id
                                            ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                            : 'border-border bg-background hover:border-primary hover:bg-primary/5'
                                    "
                                    @click="chooseCourt(court.id)"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <MapPin class="size-3.5" />
                                        {{ court.name }}
                                    </span>
                                    <Check
                                        v-if="courtId === court.id"
                                        class="size-4"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl border bg-muted/30 p-3">
                            <div
                                class="flex items-center justify-between text-xs font-medium text-muted-foreground"
                            >
                                <span>Jadwal terisi</span>
                                <span>{{ existingBookings.length }}</span>
                            </div>
                            <div
                                v-if="existingBookings.length"
                                class="mt-2 space-y-1.5"
                            >
                                <div
                                    v-for="booking in existingBookings"
                                    :key="`${booking.court_id}-${booking.start_time}`"
                                    class="flex items-center justify-between gap-2 text-xs"
                                >
                                    <span class="truncate">
                                        {{ booking.court_name }} ·
                                        {{ booking.start_time }}–{{
                                            booking.end_time
                                        }}
                                    </span>
                                    <span
                                        class="shrink-0 text-muted-foreground"
                                    >
                                        {{ booking.customer_name || 'Umum' }}
                                    </span>
                                </div>
                            </div>
                            <p
                                v-else
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                Belum ada jadwal terisi pada tanggal ini.
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Checkout -->
            <div
                class="h-fit rounded-2xl border bg-card p-4 shadow-sm lg:sticky lg:top-6"
            >
                <div class="flex items-center gap-2 border-b pb-3">
                    <ShoppingCart class="size-5 text-primary" />
                    <h2 class="font-semibold">Ringkasan</h2>
                </div>

                <div v-if="activeService === 'session' && sessionMode === 'class'" class="space-y-4 pt-4">
                    <div class="font-medium">{{ selectedClassSchedule?.class_name || 'Pendaftaran kelas' }}</div>
                    <div class="text-xs text-muted-foreground">{{ classDate || '—' }} · {{ participantNames.length }} peserta</div>
                    <div class="flex justify-between text-sm"><span>Harga / peserta</span><span>{{ formatIDR(unitPrice) }}</span></div>
                    <div class="flex justify-between border-t pt-2 font-semibold"><span>Total</span><span>{{ formatIDR(total) }}</span></div>
                    <div class="grid gap-2">
                        <Label class="text-xs">Metode pembayaran</Label>
                        <FormSelect v-model="form.payment_method" :options="methodOptions" />
                    </div>
                    <div class="grid gap-2">
                        <Label class="text-xs">Status pembayaran</Label>
                        <FormSelect v-model="form.payment_status" :options="statusOptions" />
                    </div>
                    <Button class="w-full" :disabled="form.processing || !canSubmit" @click="submit">{{ form.processing ? 'Menyimpan...' : 'Simpan transaksi' }}</Button>
                </div>

                <div v-else-if="selected" class="space-y-4 pt-4">
                    <div>
                        <div class="font-medium">{{ selected.name }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ activeFacility?.name }}
                        </div>
                    </div>

                    <div
                        v-if="!isSession && !isMembership && selected.pricing_rules.length"
                        class="grid gap-2"
                    >
                        <Label for="pos-rule" class="text-xs">Harga</Label>
                        <FormSelect
                            id="pos-rule"
                            v-model="ruleId"
                            :options="
                                selected.pricing_rules.map((rule) => ({
                                    value: String(rule.id),
                                    label: `${rule.label} — ${formatIDR(rule.price)}`,
                                }))
                            "
                        />
                    </div>

                    <div v-if="isMembership" class="grid gap-2">
                        <Label class="text-xs">Member</Label>
                        <FormSelect
                            v-model="memberId"
                            placeholder="Pilih member"
                            :options="memberOptions"
                        />
                        <p
                            v-if="!memberOptions.length"
                            class="text-xs text-muted-foreground"
                        >
                            Belum ada member. Tambahkan dulu di menu Member.
                        </p>
                    </div>

                    <div
                        v-if="isSession"
                        class="space-y-1 rounded-xl bg-muted/40 p-3 text-sm"
                    >
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Tanggal</span>
                            <span class="font-medium">{{ date || '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Jam</span>
                            <span class="font-medium">
                                {{
                                    selectedSlot
                                        ? `${selectedSlot.start_time}–${selectedSlot.end_time}`
                                        : '—'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Lapangan</span>
                            <span class="font-medium">
                                {{
                                    selectedSlot?.courts.find(
                                        (court) => court.id === courtId,
                                    )?.name ?? '—'
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-else-if="isMembership"
                        class="space-y-1 rounded-xl bg-muted/40 p-3 text-sm"
                    >
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Aktivasi</span>
                            <span class="font-medium">
                                {{ membershipPeriod?.start ?? '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Expired</span>
                            <span class="font-medium">
                                {{ membershipPeriod?.end ?? '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground"
                                >Masa aktif</span
                            >
                            <span class="font-medium">
                                {{ selected.duration_days ?? 30 }} hari
                            </span>
                        </div>
                    </div>

                    <div v-else class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Qty</span>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                :disabled="quantity <= 1"
                                @click="changeQty(-1)"
                            >
                                <Minus class="size-4" />
                            </Button>
                            <span class="w-8 text-center font-medium">{{
                                quantity
                            }}</span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                class="size-8"
                                @click="changeQty(1)"
                            >
                                <Plus class="size-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="pos-customer" class="text-xs"
                            >Nama pelanggan (opsional)</Label
                        >
                        <Input
                            id="pos-customer"
                            v-model="form.customer_name"
                            placeholder="Umum / walk-in"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs">Metode pembayaran</Label>
                        <FormSelect
                            v-model="form.payment_method"
                            :options="methodOptions"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label class="text-xs">Status pembayaran</Label>
                        <FormSelect
                            v-model="form.payment_status"
                            :options="statusOptions"
                        />
                    </div>

                    <div class="space-y-2 rounded-xl bg-muted/40 p-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Harga</span>
                            <span>{{ formatIDR(unitPrice) }}</span>
                        </div>
                        <div
                            v-if="!isSession"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-muted-foreground">Qty</span>
                            <span>{{ quantity }}</span>
                        </div>
                        <div
                            class="flex items-center justify-between border-t pt-2"
                        >
                            <span class="font-medium">Total</span>
                            <span class="text-lg font-semibold text-primary">
                                {{ formatIDR(total) }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="form.errors.court_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.court_id }}
                    </p>
                    <p
                        v-if="form.errors.start_time"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.start_time }}
                    </p>

                    <Button
                        class="w-full"
                        :disabled="form.processing || !canSubmit"
                        @click="submit"
                    >
                        {{
                            form.processing
                                ? 'Menyimpan...'
                                : 'Simpan transaksi'
                        }}
                    </Button>
                </div>

                <div
                    v-else
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    <ShoppingCart
                        class="mx-auto size-10 text-muted-foreground/40"
                    />
                    <p class="mt-3">
                        Pilih paket di sebelah kiri untuk memulai.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
