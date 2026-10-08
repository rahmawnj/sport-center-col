<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Boxes } from '@lucide/vue';
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Sport = { id: number; name: string };
type ResourceData = {
    id: number;
    sport_id: number;
    name: string;
    capacity: string;
    is_active: string;
};

const props = defineProps<{
    sports: Sport[];
    defaultSportId?: number | null;
    resource?: ResourceData | null;
}>();

const pageTitle = computed(() =>
    props.resource ? 'Edit Lapangan' : 'Tambah Lapangan',
);
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
    props.defaultSportId ? `/sports/${props.defaultSportId}` : '/resources',
);

const form = useForm<{
    sport_id: string;
    name: string;
    capacity: string;
    is_active: string;
}>({
    sport_id: props.resource
        ? String(props.resource.sport_id)
        : props.defaultSportId
          ? String(props.defaultSportId)
          : '',
    name: props.resource?.name ?? '',
    capacity: props.resource?.capacity ?? '',
    is_active: props.resource?.is_active ?? '1',
});

function setActive(value: boolean | 'indeterminate') {
    form.is_active = value === true ? '1' : '0';
}

function submit() {
    if (props.resource) {
        form.put(`/resources/${props.resource.id}`);
    } else {
        form.post('/resources');
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <form class="space-y-6 p-4 md:p-6" @submit.prevent="submit">
        <PageHeader
            eyebrow="Data Master"
            :title="pageTitle"
            description="Tambahkan fasilitas atau lapangan untuk olahraga ini."
        >
            <Button variant="outline" as-child>
                <Link :href="backHref">
                    <ArrowLeft class="mr-2 size-4" /> Kembali
                </Link>
            </Button>
        </PageHeader>

        <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <header class="flex items-start gap-3 border-b p-5">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Boxes class="size-5" />
                </div>
                <div>
                    <h2 class="font-semibold">Informasi lapangan</h2>
                    <p class="text-sm text-muted-foreground">
                        Nama, kapasitas, dan status lapangan.
                    </p>
                </div>
            </header>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
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
                        Nama lapangan
                        <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Lapangan 1"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="capacity">Kapasitas (opsional)</Label>
                    <Input
                        id="capacity"
                        v-model="form.capacity"
                        type="number"
                        min="1"
                        placeholder="e.g. 10"
                    />
                    <p
                        v-if="form.errors.capacity"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.capacity }}
                    </p>
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors hover:bg-muted/40"
                    >
                        <Checkbox
                            class="mt-0.5"
                            :model-value="form.is_active === '1'"
                            @update:model-value="setActive"
                        />
                        <span class="grid gap-0.5">
                            <span class="text-sm font-medium">Aktif</span>
                            <span class="text-xs text-muted-foreground"
                                >Lapangan dapat dibooking.</span
                            >
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 z-10 -mx-4 flex items-center justify-end gap-2 border-t bg-background/80 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
        >
            <span class="mr-auto hidden text-sm text-muted-foreground sm:block">
                {{ form.name || 'Lapangan baru' }}
            </span>
            <Button type="button" variant="ghost" as-child>
                <Link :href="backHref">Batal</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                {{
                    form.processing
                        ? 'Menyimpan...'
                        : props.resource
                          ? 'Simpan perubahan'
                          : 'Simpan lapangan'
                }}
            </Button>
        </div>
    </form>
</template>
