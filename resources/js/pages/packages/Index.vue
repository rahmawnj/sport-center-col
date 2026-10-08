<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Package,
    Percent,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTableCard from '@/components/DataTableCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import SportFilter from '@/components/SportFilter.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { TableColumn } from '@/types';

type Sport = { id: number; name: string };
type PackageRate = {
    id: number;
    day_of_week: number | null;
    start_time: string | null;
    end_time: string | null;
    date_start: string | null;
    date_end: string | null;
    price: number | null;
    priority: number | null;
};
type PackageRow = {
    id: number;
    sport_id: number;
    sport_name: string | null;
    name: string;
    description: string | null;
    pricing_type: string;
    price: number;
    duration_value: number | null;
    duration_unit: string | null;
    session_count: number | null;
    is_promo: boolean;
    requires_active_membership: boolean;
    is_active: boolean;
    created_at: string | null;
    rates: PackageRate[];
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    packages: {
        data: PackageRow[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    sports: Sport[];
    filters: { search: string; sport_id: string; type: string };
    stats: { total: number; active: number; promo: number };
};

const props = defineProps<Props>();
const columns: TableColumn[] = [
    { key: 'name', label: 'Paket', class: 'min-w-56' },
    { key: 'price', label: 'Harga' },
    { key: 'duration', label: 'Durasi' },
    { key: 'promo', label: 'Promo', align: 'center' },
    { key: 'status', label: 'Status' },
    {
        key: 'actions',
        label: 'Aksi',
        align: 'right',
        class: 'w-0 whitespace-nowrap',
    },
];
const packageTypeOptions = [
    { value: 'membership', label: 'Membership' },
    { value: 'per_visit', label: 'Per Kunjungan' },
    { value: 'per_hour', label: 'Per Jam' },
    { value: 'unlimited', label: 'Sepuasnya' },
    { value: 'trainer_session', label: 'Sesi Trainer' },
];
const typeOptions = [
    { value: 'all', label: 'Semua jenis' },
    ...packageTypeOptions,
];
const unitLabel: Record<string, string> = {
    day: 'hari',
    week: 'minggu',
    month: 'bulan',
    year: 'tahun',
};
const typeLabel: Record<string, string> = Object.fromEntries(
    packageTypeOptions.map((option) => [option.value, option.label]),
);

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? 'all');
const sportId = ref(props.filters.sport_id ?? 'all');
const statCards = computed(() => [
    { label: 'Total paket', value: props.stats.total, icon: Package },
    { label: 'Paket aktif', value: props.stats.active, icon: CheckCircle2 },
    { label: 'Sedang promo', value: props.stats.promo, icon: Percent },
]);

function filter() {
    router.get(
        '/packages',
        {
            search: search.value || undefined,
            sport_id: sportId.value === 'all' ? undefined : sportId.value,
            type: type.value === 'all' ? undefined : type.value,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    type.value = 'all';
    sportId.value = 'all';
    filter();
}

function removePackage(item: PackageRow) {
    if (!window.confirm(`Hapus paket ${item.name}?`)) {
        return;
    }

    router.delete(`/packages/${item.id}`, { preserveScroll: true });
}

function formatPrice(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDuration(value: number | null, unit: string | null) {
    if (!value || !unit) {
        return '—';
    }

    return `${value} ${unitLabel[unit] ?? unit}`;
}

const dayLabel: Record<number, string> = {
    0: 'Minggu',
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
};

function formatDay(day: number | null) {
    return day === null ? 'Semua hari' : (dayLabel[day] ?? '—');
}

function formatTime(rate: PackageRate) {
    if (!rate.start_time && !rate.end_time) {
        return '—';
    }

    return `${rate.start_time ?? '—'} – ${rate.end_time ?? '—'}`;
}

function formatPeriod(rate: PackageRate) {
    if (!rate.date_start && !rate.date_end) {
        return null;
    }

    return `${rate.date_start ?? '…'} s/d ${rate.date_end ?? '…'}`;
}
</script>

<template>
    <Head title="Packages / Paket" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Packages / Paket"
            description="Kelola paket dan skema harga untuk setiap cabang olahraga."
        >
            <Button as-child>
                <Link href="/sports/packages/create"
                    ><Plus class="mr-2 size-4" /> Tambah paket</Link
                >
            </Button>
        </PageHeader>

        <SummaryCards :items="statCards" />

        <SportFilter
            v-model="sportId"
            :sports="props.sports"
            @change="filter"
        />

        <DataTableCard
            :columns="columns"
            :rows="props.packages.data"
            :paginator="props.packages"
            total-label="paket"
        >
            <template #filters>
                <TableFilters
                    v-model:search="search"
                    v-model:status="type"
                    search-placeholder="Cari paket..."
                    :status-options="typeOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </template>

            <template #name="{ row }">
                <div class="min-w-0">
                    <div class="truncate font-medium">{{ row.name }}</div>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-xs text-muted-foreground">{{
                            row.sport_name ?? '—'
                        }}</span>
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >{{
                                typeLabel[row.pricing_type] ?? row.pricing_type
                            }}</span
                        >
                    </div>
                </div>
            </template>

            <template #price="{ row }">
                <span class="font-medium">{{ formatPrice(row.price) }}</span>
            </template>

            <template #duration="{ row }">
                <span class="text-muted-foreground">{{
                    formatDuration(row.duration_value, row.duration_unit)
                }}</span>
            </template>

            <template #promo="{ row }">
                <span
                    v-if="row.is_promo"
                    class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600"
                >
                    <span class="size-1.5 rounded-full bg-amber-500" />
                    Promo
                </span>
                <span v-else class="text-xs text-muted-foreground">—</span>
            </template>

            <template #status="{ row }">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="
                        row.is_active
                            ? 'bg-emerald-500/10 text-emerald-600'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    <span
                        class="size-1.5 rounded-full"
                        :class="
                            row.is_active
                                ? 'bg-emerald-500'
                                : 'bg-muted-foreground/60'
                        "
                    />
                    {{ row.is_active ? 'Aktif' : 'Tidak aktif' }}
                </span>
            </template>

            <template #actions="{ row }">
                <div
                    class="flex justify-end gap-1 opacity-70 transition-opacity group-hover:opacity-100"
                >
                    <Dialog v-if="row.rates.length">
                        <DialogTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                title="Lihat rate"
                            >
                                <Clock class="size-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Rate {{ row.name }}</DialogTitle>
                                <DialogDescription>
                                    Harga yang berlaku per hari/jam.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="max-h-80 space-y-2 overflow-y-auto">
                                <div
                                    v-for="rate in row.rates"
                                    :key="rate.id"
                                    class="rounded-lg border p-3 text-sm"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <span class="font-medium">{{
                                            formatDay(rate.day_of_week)
                                        }}</span>
                                        <span class="font-medium">{{
                                            rate.price !== null
                                                ? formatPrice(rate.price)
                                                : 'Harga dasar'
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground"
                                    >
                                        <span>{{ formatTime(rate) }}</span>
                                        <span v-if="formatPeriod(rate)">{{
                                            formatPeriod(rate)
                                        }}</span>
                                        <span
                                            >Prioritas
                                            {{ rate.priority ?? 0 }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Hapus"
                        class="hover:bg-destructive/10"
                        @click="removePackage(row)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </template>

            <template #empty>
                <Package class="mx-auto size-10 text-muted-foreground/50" />
                <p class="mt-3 font-medium">Belum ada paket</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Pilih olahraga lain atau tambahkan paket baru.
                </p>
            </template>
        </DataTableCard>
    </div>
</template>
