<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    EllipsisVertical,
    Eye,
    Globe,
    MapPin,
    Package as PackageIcon,
    Pencil,
    Plus,
    Trash2,
    Wrench,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
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
type Facility = {
    id: number;
    name: string;
    slug: string;
    is_bookable_online: boolean;
    turnaround_minutes: number;
    status: FacilityStatus;
    courts_count: number;
    packages_count: number;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    facilities: {
        data: Facility[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string; status: string };
    stats: { total: number; active: number; maintenance: number };
};

const props = defineProps<Props>();
const showForm = ref(false);
const editing = ref<Facility | null>(null);
const formKey = ref(0);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const title = computed(() =>
    editing.value ? 'Edit fasilitas' : 'Tambah fasilitas',
);
const action = computed(() =>
    editing.value ? `/facilities/${editing.value.id}` : '/facilities',
);
const method = computed(() => (editing.value ? 'put' : 'post'));
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'maintenance', label: 'Perawatan' },
    { value: 'inactive', label: 'Tidak aktif' },
];
const facilityStatusOptions = statusOptions.filter((o) => o.value !== 'all');
const bookableOptions = [
    { value: '1', label: 'Ya — bisa dibooking online' },
    { value: '0', label: 'Tidak' },
];
const form = ref({
    status: 'active',
    is_bookable_online: '1',
    turnaround_minutes: '5',
});
const statCards = computed(() => [
    {
        label: 'Total fasilitas',
        value: props.stats.total,
        icon: Building2,
        accent: 'sky' as const,
    },
    {
        label: 'Aktif',
        value: props.stats.active,
        icon: CheckCircle2,
        accent: 'emerald' as const,
    },
    {
        label: 'Perawatan',
        value: props.stats.maintenance,
        icon: Wrench,
        accent: 'amber' as const,
    },
]);

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

const headerGradient: Record<FacilityStatus, string> = {
    active: 'from-emerald-500 via-teal-500 to-cyan-600',
    maintenance: 'from-amber-500 via-orange-500 to-orange-600',
    inactive: 'from-slate-400 via-slate-500 to-slate-600',
};

function openCreate() {
    editing.value = null;
    form.value = {
        status: 'active',
        is_bookable_online: '1',
        turnaround_minutes: '5',
    };
    formKey.value++;
    showForm.value = true;
}

function openEdit(facility: Facility) {
    editing.value = facility;
    form.value = {
        status: facility.status,
        is_bookable_online: facility.is_bookable_online ? '1' : '0',
        turnaround_minutes: String(facility.turnaround_minutes ?? 5),
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
        '/facilities',
        {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    status.value = 'all';
    filter();
}

function removeFacility(facility: Facility) {
    if (!window.confirm(`Hapus fasilitas ${facility.name}?`)) {
        return;
    }

    router.delete(`/facilities/${facility.id}`, { preserveScroll: true });
}

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
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
    <Head title="Fasilitas" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Fasilitas"
            description="Kelola fasilitas olahraga yang tersedia di Sport Center."
        >
            <Button @click="openCreate"
                ><Plus class="mr-2 size-4" /> Tambah fasilitas</Button
            >
        </PageHeader>

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div
                class="rounded-2xl border bg-gradient-to-br from-card to-muted/30 p-4 shadow-sm"
            >
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari fasilitas..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </div>

            <div
                v-if="props.facilities.data.length"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="row in props.facilities.data"
                    :key="row.id"
                    class="group flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-lg"
                >
                    <div
                        class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br"
                        :class="headerGradient[row.status]"
                    >
                        <div
                            class="absolute -top-8 -right-8 size-28 rounded-full bg-white/20 blur-2xl"
                        ></div>
                        <div
                            class="absolute -bottom-10 -left-6 size-24 rounded-full bg-black/10 blur-2xl"
                        ></div>
                        <span
                            class="relative text-3xl font-bold tracking-tight text-white drop-shadow-sm"
                        >
                            {{ initials(row.name) }}
                        </span>
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="truncate font-semibold">
                                    {{ row.name }}
                                </div>
                                <div
                                    class="truncate font-mono text-xs text-muted-foreground"
                                >
                                    /{{ row.slug }}
                                </div>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusMeta[row.status].badge"
                            >
                                <span
                                    class="size-1.5 rounded-full"
                                    :class="statusMeta[row.status].dot"
                                />
                                {{ statusMeta[row.status].label }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-sky-500/10 px-2 py-1 text-xs font-medium text-sky-700 dark:text-sky-400"
                            >
                                <MapPin class="size-3.5" />
                                {{ row.courts_count }} lapangan
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-violet-500/10 px-2 py-1 text-xs font-medium text-violet-700 dark:text-violet-400"
                            >
                                <PackageIcon class="size-3.5" />
                                {{ row.packages_count }} paket
                            </span>
                            <span
                                v-if="row.is_bookable_online"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                <Globe class="size-3.5" />
                                Online
                            </span>
                        </div>

                        <div
                            class="mt-auto flex items-center justify-between border-t pt-3"
                        >
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(row.created_at) }}
                            </span>
                            <div class="flex gap-1">
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
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-40"
                                    >
                                        <DropdownMenuItem :as-child="true">
                                            <Link
                                                :href="`/facilities/${row.id}`"
                                                class="w-full cursor-pointer"
                                            >
                                                <Eye class="size-4" />
                                                Detail
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="openEdit(row)"
                                        >
                                            <Pencil class="size-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            variant="destructive"
                                            :disabled="
                                                row.courts_count > 0 ||
                                                row.packages_count > 0
                                            "
                                            @click="removeFacility(row)"
                                        >
                                            <Trash2 class="size-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-10 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500/15 to-violet-500/15 text-primary"
                >
                    <Building2 class="size-7" />
                </div>
                <p class="mt-4 font-medium">Belum ada fasilitas</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba ubah filter atau tambahkan fasilitas baru.
                </p>
            </div>

            <div
                v-if="props.facilities.last_page > 1"
                class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ props.facilities.current_page }} dari
                    {{ props.facilities.last_page }} ·
                    {{ props.facilities.total }} fasilitas
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in props.facilities.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        @click.prevent="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    >
                        <Button
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                        >
                            <span v-html="link.label" />
                        </Button>
                    </a>
                </div>
            </div>
        </div>
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
                        Manajemen fasilitas
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
                        <Label for="facility-name">Nama fasilitas</Label>
                        <Input
                            id="facility-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. GOR Badminton"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="facility-status">Status</Label>
                            <FormSelect
                                id="facility-status"
                                v-model="form.status"
                                name="status"
                                :options="facilityStatusOptions"
                            />
                            <p
                                v-if="errors.status"
                                class="text-xs text-destructive"
                            >
                                {{ errors.status }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="facility-bookable"
                                >Booking online</Label
                            >
                            <FormSelect
                                id="facility-bookable"
                                v-model="form.is_bookable_online"
                                name="is_bookable_online"
                                :options="bookableOptions"
                            />
                            <p
                                v-if="errors.is_bookable_online"
                                class="text-xs text-destructive"
                            >
                                {{ errors.is_bookable_online }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="facility-turnaround"
                                >Jeda antar booking (menit)</Label
                            >
                            <Input
                                id="facility-turnaround"
                                name="turnaround_minutes"
                                type="number"
                                min="0"
                                max="120"
                                :default-value="
                                    editing?.turnaround_minutes ?? 5
                                "
                                required
                            />
                            <p
                                v-if="errors.turnaround_minutes"
                                class="text-xs text-destructive"
                            >
                                {{ errors.turnaround_minutes }}
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
                                  : 'Tambah fasilitas'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
