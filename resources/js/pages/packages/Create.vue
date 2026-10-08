<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Clock,
    ListChecks,
    Package,
    Plus,
    Trash2,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Sport = { id: number; name: string };
type Rate = {
    day_of_week: string;
    start_time: string;
    end_time: string;
    date_start: string;
    date_end: string;
    price: string;
    priority: string;
};
type PackageData = {
    id: number;
    sport_id: number;
    name: string;
    description: string | null;
    price: string;
    pricing_type: string;
    duration_value: string;
    duration_unit: string;
    session_count: string;
    is_promo: string;
    requires_active_membership: string;
    is_active: string;
    rates: Rate[];
};

const props = defineProps<{
    sports: Sport[];
    defaultSportId?: number | null;
    package?: PackageData | null;
}>();

const pageTitle = computed(() =>
    props.package ? 'Edit Paket' : 'Tambah Paket',
);

const pricingTypeOptions = [
    { value: 'per_visit', label: 'Reguler (per kunjungan)' },
    { value: 'per_hour', label: 'Per jam' },
    { value: 'membership', label: 'Membership' },
    { value: 'unlimited', label: 'Sepuasnya' },
    { value: 'trainer_session', label: 'Sesi trainer' },
];
const unitOptions = [
    { value: 'hour', label: 'Jam' },
    { value: 'day', label: 'Hari' },
    { value: 'week', label: 'Minggu' },
    { value: 'month', label: 'Bulan' },
    { value: 'year', label: 'Tahun' },
];
const dayOptions = [
    { value: 'all', label: 'Semua hari' },
    { value: '0', label: 'Minggu' },
    { value: '1', label: 'Senin' },
    { value: '2', label: 'Selasa' },
    { value: '3', label: 'Rabu' },
    { value: '4', label: 'Kamis' },
    { value: '5', label: 'Jumat' },
    { value: '6', label: 'Sabtu' },
];
const sportOptions = computed(() =>
    props.sports.map((sport) => ({
        value: String(sport.id),
        label: sport.name,
    })),
);
const selectedSportName = computed(
    () =>
        props.sports.find((sport) => sport.id === props.defaultSportId)?.name ??
        '—',
);
const backHref = computed(() =>
    props.defaultSportId ? `/sports/${props.defaultSportId}` : '/packages',
);

const form = useForm<{
    sport_id: string;
    name: string;
    description: string;
    price: string;
    pricing_type: string;
    duration_value: string;
    duration_unit: string;
    session_count: string;
    is_promo: string;
    requires_active_membership: string;
    is_active: string;
    rates: Rate[];
}>({
    sport_id: props.package
        ? String(props.package.sport_id)
        : props.defaultSportId
          ? String(props.defaultSportId)
          : '',
    name: props.package?.name ?? '',
    description: props.package?.description ?? '',
    price: props.package?.price ?? '',
    pricing_type: props.package?.pricing_type ?? 'per_visit',
    duration_value: props.package?.duration_value ?? '',
    duration_unit: props.package?.duration_unit ?? 'month',
    session_count: props.package?.session_count ?? '',
    is_promo: props.package?.is_promo ?? '0',
    requires_active_membership:
        props.package?.requires_active_membership ?? '0',
    is_active: props.package?.is_active ?? '1',
    rates: props.package?.rates ?? [],
});

function setBool(
    field: 'is_promo' | 'requires_active_membership' | 'is_active',
    value: boolean | 'indeterminate',
) {
    form[field] = value === true ? '1' : '0';
}

function addRate() {
    form.rates.push({
        day_of_week: 'all',
        start_time: '',
        end_time: '',
        date_start: '',
        date_end: '',
        price: '',
        priority: '0',
    });
}

function removeRate(index: number) {
    form.rates.splice(index, 1);
}

function submit() {
    form.transform((data) => ({
        ...data,
        rates: data.rates.map((rate, index) => ({
            ...rate,
            day_of_week: rate.day_of_week === 'all' ? '' : rate.day_of_week,
            priority: String(index),
        })),
    }));

    if (props.package) {
        form.put(`/packages/${props.package.id}`);
    } else {
        form.post('/packages');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <form class="space-y-6 p-4 md:p-6" @submit.prevent="submit">
        <PageHeader
            eyebrow="Data Master"
            :title="pageTitle"
            description="Lengkapi informasi paket, harga, lalu simpan."
        >
            <Button variant="outline" as-child>
                <Link :href="backHref">
                    <ArrowLeft class="mr-2 size-4" /> Kembali
                </Link>
            </Button>
        </PageHeader>

        <div class="space-y-6">
            <section
                class="overflow-hidden rounded-2xl border bg-card shadow-sm"
            >
                <header class="flex items-start gap-3 border-b p-5">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Package class="size-5" />
                    </div>
                    <div>
                        <h2 class="font-semibold">Informasi paket</h2>
                        <p class="text-sm text-muted-foreground">
                            Data dasar, harga, dan persyaratan paket.
                        </p>
                    </div>
                </header>

                <div class="space-y-6 p-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Olahraga</Label>
                            <div
                                v-if="props.defaultSportId"
                                class="flex h-9 items-center rounded-md border bg-muted/30 px-3 text-sm"
                            >
                                {{ selectedSportName }}
                            </div>
                            <template v-else>
                                <FormSelect
                                    id="sport"
                                    v-model="form.sport_id"
                                    placeholder="Pilih olahraga"
                                    :options="sportOptions"
                                />
                                <p
                                    v-if="form.errors.sport_id"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.sport_id }}
                                </p>
                            </template>
                        </div>
                        <div class="grid gap-2">
                            <Label for="name">
                                Nama paket
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Sewa Lapangan Padel"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="description"
                                >Deskripsi (opsional)</Label
                            >
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                placeholder="Keterangan singkat paket"
                                class="min-h-16 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <p
                                v-if="form.errors.description"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4 border-t pt-5">
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Wallet class="size-4 text-primary" /> Harga
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="price">
                                    Harga dasar
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="price"
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="120000"
                                />
                                <p
                                    v-if="form.errors.price"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.price }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="pricing-type">
                                    Tipe paket
                                    <span class="text-destructive">*</span>
                                </Label>
                                <FormSelect
                                    id="pricing-type"
                                    v-model="form.pricing_type"
                                    :options="pricingTypeOptions"
                                />
                                <p
                                    v-if="form.errors.pricing_type"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.pricing_type }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="duration-value">Durasi</Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="duration-value"
                                        v-model="form.duration_value"
                                        type="number"
                                        min="1"
                                        placeholder="e.g. 1"
                                        class="flex-1"
                                    />
                                    <div class="w-28">
                                        <FormSelect
                                            v-model="form.duration_unit"
                                            :options="unitOptions"
                                        />
                                    </div>
                                </div>
                                <p
                                    v-if="form.errors.duration_value"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.duration_value }}
                                </p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="session-count">
                                    Jumlah sesi (opsional)
                                </Label>
                                <Input
                                    id="session-count"
                                    v-model="form.session_count"
                                    type="number"
                                    min="1"
                                    placeholder="e.g. 8"
                                />
                                <p
                                    v-if="form.errors.session_count"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors.session_count }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 border-t pt-5">
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <ListChecks class="size-4 text-primary" />
                            Persyaratan
                        </h3>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors hover:bg-muted/40"
                            >
                                <Checkbox
                                    class="mt-0.5"
                                    :model-value="form.is_active === '1'"
                                    @update:model-value="
                                        setBool('is_active', $event)
                                    "
                                />
                                <span class="grid gap-0.5">
                                    <span class="text-sm font-medium"
                                        >Aktif</span
                                    >
                                    <span class="text-xs text-muted-foreground"
                                        >Dapat dipilih pada transaksi.</span
                                    >
                                </span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors hover:bg-muted/40"
                            >
                                <Checkbox
                                    class="mt-0.5"
                                    :model-value="form.is_promo === '1'"
                                    @update:model-value="
                                        setBool('is_promo', $event)
                                    "
                                />
                                <span class="grid gap-0.5">
                                    <span class="text-sm font-medium"
                                        >Promo</span
                                    >
                                    <span class="text-xs text-muted-foreground"
                                        >Tandai sebagai sedang promo.</span
                                    >
                                </span>
                            </label>
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors hover:bg-muted/40"
                            >
                                <Checkbox
                                    class="mt-0.5"
                                    :model-value="
                                        form.requires_active_membership === '1'
                                    "
                                    @update:model-value="
                                        setBool(
                                            'requires_active_membership',
                                            $event,
                                        )
                                    "
                                />
                                <span class="grid gap-0.5">
                                    <span class="text-sm font-medium"
                                        >Wajib membership</span
                                    >
                                    <span class="text-xs text-muted-foreground"
                                        >Hanya untuk member aktif.</span
                                    >
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-2xl border bg-card shadow-sm"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Clock class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-semibold">
                                Harga khusus (opsional)
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Isi bila harga berbeda per hari, jam, atau
                                tanggal.
                            </p>
                        </div>
                    </div>
                    <Button type="button" variant="secondary" @click="addRate">
                        <Plus class="mr-2 size-4" /> Tambah
                    </Button>
                </header>

                <div class="p-5">
                    <div
                        v-if="form.rates.length === 0"
                        class="flex flex-col items-center rounded-xl border border-dashed p-8 text-center"
                    >
                        <Clock class="size-8 text-muted-foreground/50" />
                        <p class="mt-3 text-sm font-medium">
                            Belum ada harga khusus
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Harga dasar akan dipakai untuk semua waktu.
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="(rate, index) in form.rates"
                            :key="index"
                            class="rounded-xl border bg-muted/20 p-4"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="text-sm font-medium">
                                    Harga khusus {{ index + 1 }}
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:bg-destructive/10"
                                    @click="removeRate(index)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>

                            <div
                                class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div class="grid gap-2">
                                    <Label :for="`day-${index}`">Hari</Label>
                                    <FormSelect
                                        :id="`day-${index}`"
                                        v-model="rate.day_of_week"
                                        :options="dayOptions"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`start-${index}`"
                                        >Jam mulai</Label
                                    >
                                    <Input
                                        :id="`start-${index}`"
                                        v-model="rate.start_time"
                                        type="time"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`end-${index}`"
                                        >Jam selesai</Label
                                    >
                                    <Input
                                        :id="`end-${index}`"
                                        v-model="rate.end_time"
                                        type="time"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`date-start-${index}`"
                                        >Berlaku dari</Label
                                    >
                                    <Input
                                        :id="`date-start-${index}`"
                                        v-model="rate.date_start"
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`date-end-${index}`"
                                        >Sampai</Label
                                    >
                                    <Input
                                        :id="`date-end-${index}`"
                                        v-model="rate.date_end"
                                        type="date"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`rate-price-${index}`"
                                        >Harga</Label
                                    >
                                    <Input
                                        :id="`rate-price-${index}`"
                                        v-model="rate.price"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        placeholder="Kosong = harga dasar"
                                    />
                                </div>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                                <p
                                    v-if="
                                        form.errors[`rates.${index}.start_time`]
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{
                                        form.errors[`rates.${index}.start_time`]
                                    }}
                                </p>
                                <p
                                    v-if="
                                        form.errors[`rates.${index}.end_time`]
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors[`rates.${index}.end_time`] }}
                                </p>
                                <p
                                    v-if="form.errors[`rates.${index}.price`]"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors[`rates.${index}.price`] }}
                                </p>
                                <p
                                    v-if="
                                        form.errors[`rates.${index}.date_end`]
                                    "
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors[`rates.${index}.date_end`] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex items-center justify-end gap-2 border-t bg-background/80 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
        >
            <span class="mr-auto hidden text-sm text-muted-foreground sm:block">
                {{ form.name || 'Paket baru' }}
            </span>
            <Button type="button" variant="ghost" as-child>
                <Link :href="backHref">Batal</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? 'Menyimpan...'
                        : props.package
                          ? 'Simpan perubahan'
                          : 'Simpan paket'
                }}
            </Button>
        </div>
    </form>
</template>
