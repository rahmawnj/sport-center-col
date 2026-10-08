<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Dumbbell,
    Eye,
    Globe,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Sport = {
    id: number;
    name: string;
    description: string;
    thumbnail: string | null;
    is_active: boolean;
    is_online: boolean;
    packages_count: number;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    sports: {
        data: Sport[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string; status: string };
    stats: { total: number; active: number; online: number };
};

const props = defineProps<Props>();
const showForm = ref(false);
const editing = ref<Sport | null>(null);
const formKey = ref(0);
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const title = computed(() =>
    editing.value ? 'Edit olahraga' : 'Tambah olahraga',
);
const action = computed(() =>
    editing.value ? `/sports/${editing.value.id}` : '/sports',
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
const onlineOptions = [
    { value: '1', label: 'Ya — tampil online' },
    { value: '0', label: 'Tidak' },
];
const form = ref({ is_active: '1', is_online: '0' });
const statCards = computed(() => [
    { label: 'Total olahraga', value: props.stats.total, icon: Dumbbell },
    { label: 'Aktif', value: props.stats.active, icon: CheckCircle2 },
    { label: 'Online', value: props.stats.online, icon: Globe },
]);

function openCreate() {
    editing.value = null;
    form.value = { is_active: '1', is_online: '0' };
    formKey.value++;
    showForm.value = true;
}

function openEdit(sport: Sport) {
    editing.value = sport;
    form.value = {
        is_active: sport.is_active ? '1' : '0',
        is_online: sport.is_online ? '1' : '0',
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
        '/sports',
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

function removeSport(sport: Sport) {
    if (!window.confirm(`Hapus olahraga ${sport.name}?`)) {
        return;
    }

    router.delete(`/sports/${sport.id}`, { preserveScroll: true });
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
    <Head title="Sport / Olahraga" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Sport / Olahraga"
            description="Kelola cabang olahraga yang tersedia di Sport Center."
        >
            <Button @click="openCreate"
                ><Plus class="mr-2 size-4" /> Tambah olahraga</Button
            >
        </PageHeader>

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari olahraga..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </div>

            <div
                v-if="props.sports.data.length"
                class="grid grid-cols-1 gap-4 md:grid-cols-3"
            >
                <div
                    v-for="row in props.sports.data"
                    :key="row.id"
                    class="flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm"
                >
                    <div
                        class="flex aspect-video items-center justify-center bg-primary/10 text-2xl font-semibold text-primary"
                    >
                        <img
                            v-if="row.thumbnail"
                            :src="row.thumbnail"
                            :alt="row.name"
                            class="size-full object-cover"
                        />
                        <span v-else>{{ initials(row.name) }}</span>
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{ row.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Sport #{{ row.id }}
                                </div>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
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
                        </div>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ row.description || '—' }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-muted-foreground"
                                >{{ row.packages_count }} paket</span
                            >
                            <span
                                v-if="row.is_online"
                                class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-primary"
                                />
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
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Detail"
                                    class="hover:bg-primary/10 hover:text-primary"
                                    as-child
                                >
                                    <Link :href="`/sports/${row.id}`">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
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
                                    :disabled="row.packages_count > 0"
                                    @click="removeSport(row)"
                                >
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-10 text-center shadow-sm"
            >
                <Dumbbell class="mx-auto size-10 text-muted-foreground/50" />
                <p class="mt-3 font-medium">Belum ada olahraga</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba ubah filter atau tambahkan olahraga baru.
                </p>
            </div>

            <div
                v-if="props.sports.last_page > 1"
                class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ props.sports.current_page }} dari
                    {{ props.sports.last_page }} · {{ props.sports.total }}
                    olahraga
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in props.sports.links"
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
                        Sport management
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ title }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Sesuai struktur tabel <code>sports</code>.
                    </p>
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
                        <Label for="sport-name">Nama olahraga</Label>
                        <Input
                            id="sport-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. Futsal"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="sport-description">Deskripsi</Label>
                        <textarea
                            id="sport-description"
                            name="description"
                            rows="3"
                            :value="editing?.description"
                            placeholder="Deskripsi singkat olahraga"
                            class="min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <p
                            v-if="errors.description"
                            class="text-xs text-destructive"
                        >
                            {{ errors.description }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="sport-thumbnail"
                            >Thumbnail (opsional)</Label
                        >
                        <Input
                            id="sport-thumbnail"
                            name="thumbnail"
                            :default-value="editing?.thumbnail ?? ''"
                            placeholder="URL gambar"
                        />
                        <p
                            v-if="errors.thumbnail"
                            class="text-xs text-destructive"
                        >
                            {{ errors.thumbnail }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="sport-active">Status</Label>
                            <FormSelect
                                id="sport-active"
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
                        <div class="grid gap-2">
                            <Label for="sport-online">Online</Label>
                            <FormSelect
                                id="sport-online"
                                v-model="form.is_online"
                                name="is_online"
                                :options="onlineOptions"
                            />
                            <p
                                v-if="errors.is_online"
                                class="text-xs text-destructive"
                            >
                                {{ errors.is_online }}
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
                                  : 'Tambah olahraga'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
