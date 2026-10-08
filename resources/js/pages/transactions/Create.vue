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
    pricing_rules: PricingRule[];
};
type Facility = {
    id: number;
    name: string;
    packages: SellPackage[];
};
type Member = { id: number; name: string };
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

const props = defineProps<{ facilities: Facility[]; members: Member[] }>();

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
const unitPrice = computed(() =>
    isSession.value
        ? (selectedSlot.value?.price ?? 0)
        : (activeRule.value?.price ?? 0),
);
const total = computed(() =>
    isSession.value ? unitPrice.value : unitPrice.value * quantity.value,
);
const canSubmit = computed(() => {
    if (!selected.value) {
        return false;
    }

    if (isSession.value) {
        return !!selectedSlot.value && !!courtId.value;
    }

    if (isMembership.value) {
        return !!memberId.value;
    }

    return true;
});

watch(facilityId, () => {
    selected.value = null;
    ruleId.value = '';
    quantity.value = 1;
    memberId.value = '';
});

watch([activeRule, quantity, selectedSlot], () => {
    form.amount = total.value;
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
    ruleId.value = String(item.pricing_rules[0]?.id ?? '');
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
    if (!selected.value) {
        return;
    }

    form.package_id = selected.value.id;
    form.pricing_rule_id = isSession.value
        ? null
        : ruleId.value
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
    if (!item.pricing_rules.length) {
        return '—';
    }

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
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="item in props.facilities"
                        :key="item.id"
                        type="button"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="
                            facilityId === item.id
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'hover:border-primary hover:text-primary'
                        "
                        @click="facilityId = item.id"
                    >
                        {{ item.name }}
                    </button>
                </div>

                <div
                    v-if="activeFacility?.packages.length"
                    class="grid gap-3 sm:grid-cols-2"
                >
                    <button
                        v-for="item in activeFacility.packages"
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
                <p v-else class="text-sm text-muted-foreground">
                    Fasilitas ini belum punya paket untuk dijual.
                </p>

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

                <div v-if="selected" class="space-y-4 pt-4">
                    <div>
                        <div class="font-medium">{{ selected.name }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ activeFacility?.name }}
                        </div>
                    </div>

                    <div
                        v-if="!isSession && selected.pricing_rules.length"
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
