<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Dumbbell, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTableCard from '@/components/DataTableCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TableColumn } from '@/types';

type Trainer = {
    id: number;
    name: string;
    specialty: string | null;
    phone: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    trainers: {
        data: Trainer[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string };
    stats: { total: number };
};

const props = defineProps<Props>();
const columns: TableColumn[] = [
    { key: 'name', label: 'Trainer', class: 'min-w-48' },
    { key: 'specialty', label: 'Spesialisasi' },
    { key: 'phone', label: 'Telepon' },
    {
        key: 'actions',
        label: 'Aksi',
        align: 'right',
        class: 'w-0 whitespace-nowrap',
    },
];

const show = ref(false);
const editing = ref<Trainer | null>(null);
const key = ref(0);
const search = ref(props.filters.search ?? '');
const title = computed(() =>
    editing.value ? 'Edit trainer' : 'Tambah trainer',
);
const action = computed(() =>
    editing.value ? `/trainers/${editing.value.id}` : '/trainers',
);
const method = computed(() => (editing.value ? 'put' : 'post'));

function create() {
    editing.value = null;
    key.value++;
    show.value = true;
}

function edit(trainer: Trainer) {
    editing.value = trainer;
    key.value++;
    show.value = true;
}

function close() {
    show.value = false;
    editing.value = null;
}

function filter() {
    router.get(
        '/trainers',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    filter();
}

function remove(trainer: Trainer) {
    if (!window.confirm(`Hapus ${trainer.name}?`)) {
        return;
    }

    router.delete(`/trainers/${trainer.id}`, { preserveScroll: true });
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
    <Head title="Trainers" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Master Data"
            title="Trainers"
            description="Kelola data trainer dan spesialisasinya."
        >
            <Button @click="create"
                ><Plus class="mr-2 size-4" /> Tambah trainer</Button
            >
        </PageHeader>

        <div class="rounded-2xl border bg-card p-4 shadow-sm">
            <div class="flex justify-between">
                <span class="text-sm text-muted-foreground"
                    >Total trainers</span
                >
                <Dumbbell class="size-4 text-muted-foreground" />
            </div>
            <div class="mt-3 text-2xl font-semibold">
                {{ props.stats.total }}
            </div>
        </div>

        <DataTableCard
            :columns="columns"
            :rows="props.trainers.data"
            :paginator="props.trainers"
            total-label="trainer"
        >
            <template #filters>
                <TableFilters
                    v-model:search="search"
                    search-placeholder="Cari trainer..."
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
                        <Link
                            :href="`/trainers/${row.id}`"
                            class="truncate font-medium text-primary hover:underline"
                        >
                            {{ row.name }}
                        </Link>
                        <div class="text-xs text-muted-foreground">
                            Trainer #{{ row.id }}
                        </div>
                    </div>
                </div>
            </template>

            <template #specialty="{ row }">
                <span
                    v-if="row.specialty"
                    class="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                >
                    {{ row.specialty }}
                </span>
                <span v-else class="text-xs text-muted-foreground">—</span>
            </template>

            <template #phone="{ row }">
                <span class="text-muted-foreground tabular-nums">
                    {{ row.phone || '—' }}
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
                        @click="edit(row)"
                    >
                        <Pencil class="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Hapus"
                        class="hover:bg-destructive/10"
                        @click="remove(row)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </template>

            <template #empty>
                <Dumbbell class="mx-auto size-10 text-muted-foreground/50" />
                <p class="mt-3 font-medium">Belum ada trainer</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba ubah pencarian atau tambahkan trainer baru.
                </p>
            </template>
        </DataTableCard>
    </div>

    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="close"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Trainer management
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ title }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="close"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="key"
                :action="action"
                :method="method"
                class="space-y-5 p-5"
                @success="close"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="trainer-name">Nama trainer</Label>
                        <Input
                            id="trainer-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="e.g. Budi Santoso"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="trainer-specialty"
                            >Spesialisasi (opsional)</Label
                        >
                        <Input
                            id="trainer-specialty"
                            name="specialty"
                            :default-value="editing?.specialty ?? ''"
                            placeholder="e.g. Personal Trainer"
                        />
                        <p
                            v-if="errors.specialty"
                            class="text-xs text-destructive"
                        >
                            {{ errors.specialty }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="trainer-phone">Telepon (opsional)</Label>
                        <Input
                            id="trainer-phone"
                            name="phone"
                            :default-value="editing?.phone ?? ''"
                            placeholder="08xxxxxxxxxx"
                        />
                        <p v-if="errors.phone" class="text-xs text-destructive">
                            {{ errors.phone }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="close"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editing
                                  ? 'Simpan perubahan'
                                  : 'Tambah trainer'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
