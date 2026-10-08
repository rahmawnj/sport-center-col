<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    Boxes,
    CheckCircle2,
    Pencil,
    Plus,
    PowerOff,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTableCard from '@/components/DataTableCard.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import SportFilter from '@/components/SportFilter.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TableColumn } from '@/types';

type Sport = { id: number; name: string };
type Resource = {
    id: number;
    sport_id: number;
    sport_name: string | null;
    name: string;
    capacity: number | null;
    is_active: boolean;
    bookings_count: number;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    resources: {
        data: Resource[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    sports: Sport[];
    filters: { search: string; sport_id: string; status: string };
    stats: { total: number; active: number; inactive: number };
};

const props = defineProps<Props>();
const columns: TableColumn[] = [
    { key: 'name', label: 'Resource', class: 'min-w-48' },
    { key: 'capacity', label: 'Kapasitas', align: 'center' },
    { key: 'status', label: 'Status' },
    { key: 'bookings_count', label: 'Booking' },
    {
        key: 'actions',
        label: 'Aksi',
        align: 'right',
        class: 'w-0 whitespace-nowrap',
    },
];

const showForm = ref(false);
const editing = ref<Resource | null>(null);
const formKey = ref(0);
const search = ref(props.filters.search ?? '');
const sportId = ref(props.filters.sport_id ?? 'all');
const status = ref(props.filters.status ?? 'all');
const title = computed(() =>
    editing.value ? 'Edit resource' : 'Tambah resource',
);
const action = computed(() =>
    editing.value ? `/resources/${editing.value.id}` : '/resources',
);
const method = computed(() => (editing.value ? 'put' : 'post'));
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Tidak aktif' },
];
const activeOptions = [
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak aktif' },
];
const sportOptions = computed(() =>
    props.sports.map((sport) => ({
        value: String(sport.id),
        label: sport.name,
    })),
);
const form = ref({ sport_id: '', is_active: '1' });
const statCards = computed(() => [
    { label: 'Total resource', value: props.stats.total, icon: Boxes },
    { label: 'Aktif', value: props.stats.active, icon: CheckCircle2 },
    { label: 'Tidak aktif', value: props.stats.inactive, icon: PowerOff },
]);

function openCreate() {
    editing.value = null;
    form.value = { sport_id: '', is_active: '1' };
    formKey.value++;
    showForm.value = true;
}

function openEdit(resource: Resource) {
    editing.value = resource;
    form.value = {
        sport_id: String(resource.sport_id),
        is_active: resource.is_active ? '1' : '0',
    };
    formKey.value++;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

function filter() {
    router.get(
        '/resources',
        {
            search: search.value || undefined,
            sport_id: sportId.value === 'all' ? undefined : sportId.value,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    sportId.value = 'all';
    status.value = 'all';
    filter();
}

function removeResource(resource: Resource) {
    if (!window.confirm(`Hapus resource ${resource.name}?`)) {
        return;
    }

    router.delete(`/resources/${resource.id}`, { preserveScroll: true });
}

function initials(value: string) {
    return value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="Resources" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Resources"
            description="Kelola fasilitas atau lapangan yang dapat dibooking untuk setiap olahraga."
        >
            <Button @click="openCreate"
                ><Plus class="mr-2 size-4" /> Tambah resource</Button
            >
        </PageHeader>

        <SummaryCards :items="statCards" />

        <SportFilter v-model="sportId" :sports="props.sports" @change="filter" />

        <DataTableCard
            :columns="columns"
            :rows="props.resources.data"
            :paginator="props.resources"
            total-label="resource"
        >
            <template #filters>
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari resource..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </template>

            <template #name="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"
                    >
                        {{ initials(row.name) }}
                    </div>
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ row.name }}</div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ row.sport_name ?? '—' }}
                        </div>
                    </div>
                </div>
            </template>

            <template #capacity="{ row }">
                <span class="text-muted-foreground">
                    {{ row.capacity ? `${row.capacity} orang` : '—' }}
                </span>
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

            <template #bookings_count="{ row }">
                <span class="text-sm text-muted-foreground">
                    {{ row.bookings_count }} booking
                </span>
            </template>

            <template #actions="{ row }">
                <div
                    class="flex justify-end gap-1 opacity-70 transition-opacity group-hover:opacity-100"
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Edit"
                        class="hover:bg-primary/10 hover:text-primary"
                        @click="openEdit(row)"
                    >
                        <Pencil class="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Hapus"
                        class="hover:bg-destructive/10"
                        :disabled="row.bookings_count > 0"
                        @click="removeResource(row)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </template>

            <template #empty>
                <Boxes class="mx-auto size-10 text-muted-foreground/50" />
                <p class="mt-3 font-medium">Belum ada resource</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Pilih olahraga lain atau tambahkan resource baru.
                </p>
            </template>
        </DataTableCard>
    </div>

    <div
        v-if="showForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeForm"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Resource management
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ title }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="formKey"
                :action="action"
                :method="method"
                class="space-y-5 p-5"
                @success="closeForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="resource-sport"
                            >Olahraga <span class="text-destructive">*</span></Label
                        >
                        <FormSelect
                            id="resource-sport"
                            v-model="form.sport_id"
                            name="sport_id"
                            placeholder="Pilih olahraga"
                            :options="sportOptions"
                        />
                        <p
                            v-if="errors.sport_id"
                            class="text-xs text-destructive"
                        >
                            {{ errors.sport_id }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="resource-name">Nama resource</Label>
                        <Input
                            id="resource-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. Lapangan 1"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="resource-capacity"
                                >Kapasitas (opsional)</Label
                            >
                            <Input
                                id="resource-capacity"
                                name="capacity"
                                type="number"
                                min="1"
                                :default-value="editing?.capacity ?? ''"
                                placeholder="e.g. 10"
                            />
                            <p
                                v-if="errors.capacity"
                                class="text-xs text-destructive"
                            >
                                {{ errors.capacity }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="resource-active">Status</Label>
                            <FormSelect
                                id="resource-active"
                                v-model="form.is_active"
                                name="is_active"
                                :options="activeOptions"
                            />
                            <p
                                v-if="errors.is_active"
                                class="text-xs text-destructive"
                            >
                                {{ errors.is_active }}
                            </p>
                        </div>
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
                                  : 'Tambah resource'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
