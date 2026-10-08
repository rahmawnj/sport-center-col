<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    Clock,
    EllipsisVertical,
    LayoutGrid,
    Package as PackageIcon,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FacilityStatus = 'active' | 'maintenance' | 'inactive';
type CourtStatus = 'available' | 'maintenance' | 'inactive';
type PackageType = 'session' | 'membership' | 'entry';
type DayType = 'weekday' | 'weekend' | 'all';
type Court = { id: number; name: string; status: CourtStatus };
type PricingRule = {
    id: number;
    day_type: DayType;
    date_start: string | null;
    date_end: string | null;
    start_time: string | null;
    end_time: string | null;
    price: number;
    priority: number;
};
type PricingRuleInput = {
    _key: number;
    day_type: DayType;
    date_start: string;
    date_end: string;
    start_time: string;
    end_time: string;
    price: string;
    priority: string;
};
type Package = {
    id: number;
    name: string;
    type: PackageType;
    duration_minutes: number;
    duration_days: number | null;
    pricing_rules: PricingRule[];
};
type Props = {
    facility: {
        id: number;
        name: string;
        slug: string;
        is_bookable_online: boolean;
        turnaround_minutes: number;
        status: FacilityStatus;
        created_at: string | null;
        courts: Court[];
        packages: Package[];
    };
};

const props = defineProps<Props>();

const statusMeta: Record<
    FacilityStatus,
    { label: string; badge: string; dot: string }
> = {
    active: {
        label: 'Aktif',
        badge: 'bg-emerald-500/10 text-emerald-600',
        dot: 'bg-emerald-500',
    },
    maintenance: {
        label: 'Perawatan',
        badge: 'bg-amber-500/10 text-amber-600',
        dot: 'bg-amber-500',
    },
    inactive: {
        label: 'Tidak aktif',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
    },
};

const courtStatusMeta: Record<CourtStatus, { label: string; badge: string }> = {
    available: {
        label: 'Tersedia',
        badge: 'bg-emerald-500/10 text-emerald-600',
    },
    maintenance: {
        label: 'Perawatan',
        badge: 'bg-amber-500/10 text-amber-600',
    },
    inactive: { label: 'Tidak aktif', badge: 'bg-muted text-muted-foreground' },
};

const packageTypeLabel: Record<PackageType, string> = {
    session: 'Sesi',
    membership: 'Keanggotaan',
    entry: 'Harian',
};
const typeOptions = (Object.keys(packageTypeLabel) as PackageType[]).map(
    (value) => ({ value, label: packageTypeLabel[value] }),
);

const dayTypeLabel: Record<DayType, string> = {
    all: 'Semua hari',
    weekday: 'Weekday',
    weekend: 'Weekend',
};
const dayTypeOptions = (Object.keys(dayTypeLabel) as DayType[]).map(
    (value) => ({ value, label: dayTypeLabel[value] }),
);

let ruleUid = 0;
function emptyRule(): PricingRuleInput {
    return {
        _key: ++ruleUid,
        day_type: 'all',
        date_start: '',
        date_end: '',
        start_time: '',
        end_time: '',
        price: '',
        priority: '0',
    };
}

const showForm = ref(false);
const editing = ref<Package | null>(null);
const formKey = ref(0);
const form = ref<{
    type: PackageType;
    duration_minutes: string;
    duration_days: string;
    pricing_rules: PricingRuleInput[];
}>({
    type: 'session',
    duration_minutes: '60',
    duration_days: '30',
    pricing_rules: [emptyRule()],
});
const formTitle = computed(() =>
    editing.value ? 'Edit paket' : 'Tambah paket',
);
const formAction = computed(() =>
    editing.value
        ? `/packages/${editing.value.id}`
        : `/facilities/${props.facility.id}/packages`,
);
const formMethod = computed(() => (editing.value ? 'put' : 'post'));

const info = computed(() => [
    { label: 'Status', value: statusMeta[props.facility.status].label },
    {
        label: 'Booking online',
        value: props.facility.is_bookable_online ? 'Ya' : 'Tidak',
    },
    {
        label: 'Jeda antar booking',
        value: `${props.facility.turnaround_minutes ?? 0} menit`,
    },
    {
        label: 'Dibuat',
        value: formatDate(props.facility.created_at),
    },
]);

function openCreate() {
    editing.value = null;
    form.value = {
        type: 'session',
        duration_minutes: '60',
        duration_days: '30',
        pricing_rules: [emptyRule()],
    };
    formKey.value++;
    showForm.value = true;
}

function openEdit(pkg: Package) {
    editing.value = pkg;
    form.value = {
        type: pkg.type,
        duration_minutes: String(pkg.duration_minutes ?? 60),
        duration_days: pkg.duration_days ? String(pkg.duration_days) : '',
        pricing_rules: pkg.pricing_rules.length
            ? pkg.pricing_rules.map((rule) => ({
                  _key: ++ruleUid,
                  day_type: rule.day_type,
                  date_start: rule.date_start ?? '',
                  date_end: rule.date_end ?? '',
                  start_time: rule.start_time ?? '',
                  end_time: rule.end_time ?? '',
                  price: String(rule.price),
                  priority: String(rule.priority ?? 0),
              }))
            : [emptyRule()],
    };
    formKey.value++;
    showForm.value = true;
}

function addRule() {
    form.value.pricing_rules.push(emptyRule());
}

function removeRule(index: number) {
    form.value.pricing_rules.splice(index, 1);
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

function removePackage(pkg: Package) {
    if (!window.confirm(`Hapus paket ${pkg.name}?`)) {
        return;
    }

    router.delete(`/packages/${pkg.id}`, { preserveScroll: true });
}

const showCourtForm = ref(false);
const editingCourt = ref<Court | null>(null);
const courtFormKey = ref(0);
const courtForm = ref({ status: 'available' });
const courtStatusOptions = [
    { value: 'available', label: 'Tersedia' },
    { value: 'maintenance', label: 'Perawatan' },
    { value: 'inactive', label: 'Tidak aktif' },
];
const courtFormTitle = computed(() =>
    editingCourt.value ? 'Edit lapangan' : 'Tambah lapangan',
);
const courtFormAction = computed(() =>
    editingCourt.value
        ? `/courts/${editingCourt.value.id}`
        : `/facilities/${props.facility.id}/courts`,
);
const courtFormMethod = computed(() => (editingCourt.value ? 'put' : 'post'));

function openCourtCreate() {
    editingCourt.value = null;
    courtForm.value = { status: 'available' };
    courtFormKey.value++;
    showCourtForm.value = true;
}

function openCourtEdit(court: Court) {
    editingCourt.value = court;
    courtForm.value = { status: court.status };
    courtFormKey.value++;
    showCourtForm.value = true;
}

function closeCourtForm() {
    showCourtForm.value = false;
    editingCourt.value = null;
}

function removeCourt(court: Court) {
    if (!window.confirm(`Hapus lapangan ${court.name}?`)) {
        return;
    }

    router.delete(`/courts/${court.id}`, { preserveScroll: true });
}

function formatDuration(minutes: number) {
    return minutes % 60 === 0 ? `${minutes / 60} jam` : `${minutes} menit`;
}

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function ruleTiming(rule: PricingRule) {
    const time =
        rule.start_time && rule.end_time
            ? `${rule.start_time}–${rule.end_time}`
            : '';

    return [dayTypeLabel[rule.day_type], time].filter(Boolean).join(' · ');
}

function promoRange(rule: PricingRule) {
    if (!rule.date_start && !rule.date_end) {
        return '';
    }

    return `Promo: ${rule.date_start ?? '…'} s/d ${rule.date_end ?? '…'}`;
}
</script>

<template>
    <Head :title="`Fasilitas · ${props.facility.name}`" />
    <div class="space-y-6 p-4 md:p-6">
        <Link
            href="/facilities"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke daftar fasilitas
        </Link>

        <PageHeader
            eyebrow="Detail fasilitas"
            :title="props.facility.name"
            :description="`/${props.facility.slug}`"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusMeta[props.facility.status].badge"
            >
                <span
                    class="size-1.5 rounded-full"
                    :class="statusMeta[props.facility.status].dot"
                />
                {{ statusMeta[props.facility.status].label }}
            </span>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="item in info"
                :key="item.label"
                class="rounded-2xl border bg-card p-4 shadow-sm"
            >
                <div class="text-sm text-muted-foreground">
                    {{ item.label }}
                </div>
                <div class="mt-2 font-medium">{{ item.value }}</div>
            </div>
        </div>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <LayoutGrid class="size-4 text-primary" />
                    <h2 class="font-semibold">Lapangan</h2>
                    <span class="text-sm text-muted-foreground"
                        >({{ props.facility.courts.length }})</span
                    >
                </div>
                <Button size="sm" @click="openCourtCreate">
                    <Plus class="mr-2 size-4" />
                    Tambah lapangan
                </Button>
            </div>

            <div
                v-if="props.facility.courts.length"
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="court in props.facility.courts"
                    :key="court.id"
                    class="flex items-center justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"
                        >
                            <Building2 class="size-4" />
                        </div>
                        <span class="truncate font-medium">{{
                            court.name
                        }}</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="courtStatusMeta[court.status].badge"
                        >
                            {{ courtStatusMeta[court.status].label }}
                        </span>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Aksi"
                                    class="hover:bg-primary/10 hover:text-primary"
                                >
                                    <EllipsisVertical class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                                <DropdownMenuItem @click="openCourtEdit(court)">
                                    <Pencil class="size-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @click="removeCourt(court)"
                                >
                                    <Trash2 class="size-4" />
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm"
            >
                Belum ada lapangan pada fasilitas ini.
            </div>
        </section>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <PackageIcon class="size-4 text-primary" />
                    <h2 class="font-semibold">Paket</h2>
                    <span class="text-sm text-muted-foreground"
                        >({{ props.facility.packages.length }})</span
                    >
                </div>
                <Button size="sm" @click="openCreate">
                    <Plus class="mr-2 size-4" />
                    Tambah paket
                </Button>
            </div>

            <div
                v-if="props.facility.packages.length"
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="pkg in props.facility.packages"
                    :key="pkg.id"
                    class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <PackageIcon class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ pkg.name }}
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span>{{
                                        packageTypeLabel[pkg.type]
                                    }}</span>
                                    <template v-if="pkg.duration_minutes > 0">
                                        <span class="text-muted-foreground/40"
                                            >·</span
                                        >
                                        <Clock class="size-3" />
                                        <span>{{
                                            formatDuration(pkg.duration_minutes)
                                        }}</span>
                                    </template>
                                    <template
                                        v-if="
                                            pkg.type === 'membership' &&
                                            pkg.duration_days
                                        "
                                    >
                                        <span class="text-muted-foreground/40"
                                            >·</span
                                        >
                                        <Clock class="size-3" />
                                        <span
                                            >{{ pkg.duration_days }} hari</span
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Aksi"
                                    class="hover:bg-primary/10 hover:text-primary"
                                >
                                    <EllipsisVertical class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                                <DropdownMenuItem @click="openEdit(pkg)">
                                    <Pencil class="size-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @click="removePackage(pkg)"
                                >
                                    <Trash2 class="size-4" />
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="mt-auto space-y-1.5 border-t pt-3">
                        <div class="text-xs text-muted-foreground">
                            Aturan harga
                        </div>
                        <template v-if="pkg.pricing_rules.length">
                            <div
                                v-for="rule in pkg.pricing_rules"
                                :key="rule.id"
                                class="flex items-center justify-between gap-2 text-sm"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-muted-foreground"
                                    :title="promoRange(rule)"
                                >
                                    {{ ruleTiming(rule) }}
                                    <span
                                        v-if="rule.date_start || rule.date_end"
                                        class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-600"
                                    >
                                        Promo
                                    </span>
                                </span>
                                <span class="font-medium">
                                    {{ formatIDR(rule.price) }}
                                </span>
                            </div>
                        </template>
                        <p v-else class="text-xs text-muted-foreground">
                            Belum ada harga.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm"
            >
                Belum ada paket pada fasilitas ini.
            </div>
        </section>
    </div>

    <div
        v-if="showForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeForm"
    >
        <div
            class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen paket
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ formTitle }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="formKey"
                :action="formAction"
                :method="formMethod"
                class="space-y-5 p-5"
                @success="closeForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="package-name">Nama paket</Label>
                        <Input
                            id="package-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. Paket Bulanan"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="package-type">Tipe</Label>
                        <FormSelect
                            id="package-type"
                            v-model="form.type"
                            name="type"
                            :options="typeOptions"
                        />
                        <p v-if="errors.type" class="text-xs text-destructive">
                            {{ errors.type }}
                        </p>
                    </div>
                    <div v-if="form.type === 'session'" class="grid gap-2">
                        <Label for="package-duration">Durasi (menit)</Label>
                        <Input
                            id="package-duration"
                            type="number"
                            name="duration_minutes"
                            :default-value="editing?.duration_minutes ?? 60"
                            required
                            placeholder="60"
                        />
                        <p
                            v-if="errors.duration_minutes"
                            class="text-xs text-destructive"
                        >
                            {{ errors.duration_minutes }}
                        </p>
                    </div>
                    <div v-if="form.type === 'membership'" class="grid gap-2">
                        <Label for="package-duration-days"
                            >Masa aktif (hari)</Label
                        >
                        <Input
                            id="package-duration-days"
                            type="number"
                            min="1"
                            name="duration_days"
                            :default-value="editing?.duration_days ?? 30"
                            required
                            placeholder="30"
                        />
                        <p
                            v-if="errors.duration_days"
                            class="text-xs text-destructive"
                        >
                            {{ errors.duration_days }}
                        </p>
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <Label>Aturan harga</Label>
                                <p class="text-[11px] text-muted-foreground">
                                    Isi rentang tanggal + prioritas untuk promo
                                    (menimpa harga normal saat aktif).
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addRule"
                            >
                                <Plus class="mr-1.5 size-3.5" />
                                Tambah harga
                            </Button>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(rule, index) in form.pricing_rules"
                                :key="rule._key"
                                class="space-y-2 rounded-lg border p-2.5"
                            >
                                <div class="grid grid-cols-12 items-end gap-2">
                                    <div
                                        class="col-span-12 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Hari</Label>
                                        <FormSelect
                                            :id="`rule-day-${index}`"
                                            v-model="rule.day_type"
                                            :name="`pricing_rules[${index}][day_type]`"
                                            :options="dayTypeOptions"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Mulai</Label>
                                        <Input
                                            type="time"
                                            v-model="rule.start_time"
                                            :name="`pricing_rules[${index}][start_time]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-3"
                                    >
                                        <Label class="text-xs">Selesai</Label>
                                        <Input
                                            type="time"
                                            v-model="rule.end_time"
                                            :name="`pricing_rules[${index}][end_time]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-10 grid gap-1 sm:col-span-2"
                                    >
                                        <Label class="text-xs">Harga</Label>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="1000"
                                            v-model="rule.price"
                                            :name="`pricing_rules[${index}][price]`"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div
                                        class="col-span-2 flex justify-end sm:col-span-1"
                                    >
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10"
                                            :disabled="
                                                form.pricing_rules.length === 1
                                            "
                                            @click="removeRule(index)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-12 items-end gap-2 border-t pt-2"
                                >
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-4"
                                    >
                                        <Label class="text-xs">
                                            Promo mulai (opsional)
                                        </Label>
                                        <Input
                                            type="date"
                                            v-model="rule.date_start"
                                            :name="`pricing_rules[${index}][date_start]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-4"
                                    >
                                        <Label class="text-xs">
                                            Promo sampai (opsional)
                                        </Label>
                                        <Input
                                            type="date"
                                            v-model="rule.date_end"
                                            :name="`pricing_rules[${index}][date_end]`"
                                        />
                                    </div>
                                    <div
                                        class="col-span-6 grid gap-1 sm:col-span-2"
                                    >
                                        <Label class="text-xs">Prioritas</Label>
                                        <Input
                                            type="number"
                                            v-model="rule.priority"
                                            :name="`pricing_rules[${index}][priority]`"
                                            placeholder="0"
                                        />
                                    </div>
                                    <p
                                        class="col-span-6 text-[11px] leading-tight text-muted-foreground sm:col-span-2"
                                    >
                                        Prioritas lebih tinggi akan dipakai saat
                                        jam &amp; tanggalnya sama.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <template v-for="(msg, key) in errors" :key="key">
                            <p
                                v-if="String(key).startsWith('pricing_rules')"
                                class="text-xs text-destructive"
                            >
                                {{ msg }}
                            </p>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editing
                                  ? 'Simpan perubahan'
                                  : 'Tambah paket'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>

    <div
        v-if="showCourtForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeCourtForm"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen lapangan
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">
                        {{ courtFormTitle }}
                    </h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeCourtForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="courtFormKey"
                :action="courtFormAction"
                :method="courtFormMethod"
                class="space-y-5 p-5"
                @success="closeCourtForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="court-name">Nama lapangan</Label>
                        <Input
                            id="court-name"
                            name="name"
                            :default-value="editingCourt?.name"
                            required
                            placeholder="e.g. Lapangan 1"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="court-status">Status</Label>
                        <FormSelect
                            id="court-status"
                            v-model="courtForm.status"
                            name="status"
                            :options="courtStatusOptions"
                        />
                        <p
                            v-if="errors.status"
                            class="text-xs text-destructive"
                        >
                            {{ errors.status }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="ghost"
                        @click="closeCourtForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editingCourt
                                  ? 'Simpan perubahan'
                                  : 'Tambah lapangan'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
