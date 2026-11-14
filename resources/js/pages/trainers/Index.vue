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
    user_id: number | null;
    user_name: string | null;
    user_email: string | null;
    facility_ids: number[];
    facilities: string[];
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
    facilities: { id: number; name: string }[];
    filters: { search: string };
    stats: { total: number };
};

const props = defineProps<Props>();
const columns: TableColumn[] = [
    { key: 'name', label: 'Trainer', class: 'min-w-48' },
    { key: 'user', label: 'Akun User' },
    { key: 'specialty', label: 'Spesialisasi' },
    { key: 'facilities', label: 'Olahraga' },
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

            <template #user="{ row }">
                <div v-if="row.user_id">
                    <div class="font-medium">{{ row.user_name }}</div>
                    <div class="text-xs text-muted-foreground">{{ row.user_email }}</div>
                </div>
                <span v-else class="text-xs text-muted-foreground">Belum terhubung</span>
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

            <template #facilities="{ row }">
                <span class="text-muted-foreground">{{ row.facilities?.join(', ') || '—' }}</span>
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
                        <Label for="trainer-email">Email login</Label>
                        <Input
                            id="trainer-email"
                            name="email"
                            type="email"
                            :default-value="editing?.user_email ?? ''"
                            required
                            placeholder="trainer@email.com"
                        />
                        <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="trainer-password">Password login</Label>
                        <Input
                            id="trainer-password"
                            name="password"
                            type="password"
                            :required="!editing || !editing.user_id"
                            :placeholder="editing?.user_id ? 'Kosongkan jika tidak ingin mengganti password' : 'Minimal 8 karakter'"
                        />
                        <p v-if="editing?.user_id" class="text-xs text-muted-foreground">Password akun saat ini tidak ditampilkan. Isi hanya jika ingin menggantinya.</p>
                        <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
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
                    <div class="grid gap-3">
                        <div>
                            <Label>Olahraga yang diajarkan</Label>
                            <p class="mt-1 text-xs text-muted-foreground">Centang semua olahraga yang dapat diajarkan trainer ini.</p>
                        </div>
                        <div v-if="props.facilities.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label
                                v-for="facility in props.facilities"
                                :key="facility.id"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border bg-background px-3 py-3 text-sm transition-colors hover:border-primary/50 hover:bg-primary/5 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                            >
                                <input
                                    type="checkbox"
                                    name="facility_ids[]"
                                    :value="facility.id"
                                    :checked="editing?.facility_ids?.includes(facility.id) ?? false"
                                    class="size-4 shrink-0 rounded border-input accent-primary"
                                />
                                <span class="font-medium">{{ facility.name }}</span>
                            </label>
                        </div>
                        <p v-else class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">Belum ada data olahraga yang tersedia.</p>
                        <p v-if="errors.facility_ids" class="text-xs text-destructive">{{ errors.facility_ids }}</p>
                        <p v-if="errors['facility_ids.0']" class="text-xs text-destructive">{{ errors['facility_ids.0'] }}</p>
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
