<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Boxes,
    Globe,
    MapPin,
    MoreVertical,
    Package,
    Pencil,
    Plus,
    Trash2,
    Users,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type PackageRow = {
    id: number;
    name: string;
    description: string | null;
    pricing_type: string;
    price: number;
    duration_value: number | null;
    duration_unit: string | null;
    session_count: number | null;
    is_promo: boolean;
    is_active: boolean;
};
type ResourceRow = {
    id: number;
    name: string;
    capacity: number | null;
    is_active: boolean;
};
type Sport = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    thumbnail: string | null;
    is_active: boolean;
    is_online: boolean;
    sort_order: number;
    packages_count: number;
    resources_count: number;
    created_at: string | null;
    packages: PackageRow[];
    resources: ResourceRow[];
};

const props = defineProps<{ sport: Sport }>();

const typeLabel: Record<string, string> = {
    membership: 'Membership',
    per_visit: 'Per Kunjungan',
    per_hour: 'Per Jam',
    unlimited: 'Sepuasnya',
    trainer_session: 'Sesi Trainer',
};
const unitLabel: Record<string, string> = {
    day: 'hari',
    week: 'minggu',
    month: 'bulan',
    year: 'tahun',
};

function formatPrice(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDuration(value: number | null, unit: string | null) {
    if (!value || !unit) {
        return null;
    }

    return `${value} ${unitLabel[unit] ?? unit}`;
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

function removePackage(item: PackageRow) {
    if (!window.confirm(`Hapus paket ${item.name}?`)) {
        return;
    }

    router.delete(`/packages/${item.id}`, { preserveScroll: true });
}

function removeResource(item: ResourceRow) {
    if (!window.confirm(`Hapus lapangan ${item.name}?`)) {
        return;
    }

    router.delete(`/resources/${item.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${props.sport.name} · Olahraga`" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            :title="props.sport.name"
            :description="props.sport.description || undefined"
        >
            <Button variant="outline" as-child>
                <Link href="/sports">
                    <ArrowLeft class="mr-2 size-4" /> Kembali
                </Link>
            </Button>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <div
                class="flex items-center justify-center overflow-hidden rounded-2xl border bg-primary/10 lg:col-span-2"
            >
                <img
                    v-if="props.sport.thumbnail"
                    :src="props.sport.thumbnail"
                    :alt="props.sport.name"
                    class="h-full max-h-72 w-full object-cover"
                />
                <span
                    v-else
                    class="py-16 text-5xl font-semibold text-primary"
                    >{{ initials(props.sport.name) }}</span
                >
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-2">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <span class="text-sm text-muted-foreground">Status</span>
                    <div class="mt-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="
                                props.sport.is_active
                                    ? 'bg-emerald-500/10 text-emerald-600'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            <span
                                class="size-1.5 rounded-full"
                                :class="
                                    props.sport.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-muted-foreground/60'
                                "
                            />
                            {{
                                props.sport.is_active ? 'Aktif' : 'Tidak aktif'
                            }}
                        </span>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground"
                            >Online</span
                        >
                        <Globe class="size-4 text-muted-foreground" />
                    </div>
                    <div class="mt-2 font-medium">
                        {{ props.sport.is_online ? 'Ya' : 'Tidak' }}
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Paket</span>
                        <Package class="size-4 text-muted-foreground" />
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.sport.packages_count }}
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground"
                            >Resource</span
                        >
                        <Boxes class="size-4 text-muted-foreground" />
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.sport.resources_count }}
                    </div>
                </div>
                <div
                    class="rounded-2xl border bg-card p-4 shadow-sm sm:col-span-2"
                >
                    <span class="text-sm text-muted-foreground">Dibuat</span>
                    <div class="mt-2 font-medium">
                        {{ formatDate(props.sport.created_at) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Slug: {{ props.sport.slug }} · Urutan:
                        {{ props.sport.sort_order }}
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b p-4">
                <div class="flex items-center gap-2">
                    <Package class="size-4 text-primary" />
                    <h2 class="font-semibold">Paket olahraga</h2>
                </div>
                <Button size="sm" as-child>
                    <Link
                        :href="`/sports/packages/create?sport_id=${props.sport.id}`"
                    >
                        <Plus class="mr-2 size-4" /> Tambah paket
                    </Link>
                </Button>
            </div>
            <div
                v-if="props.sport.packages.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                Belum ada paket untuk olahraga ini.
            </div>
            <div v-else class="grid gap-3 p-4 md:grid-cols-3">
                <div
                    v-for="item in props.sport.packages"
                    :key="item.id"
                    class="flex flex-col gap-2 rounded-xl border p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate font-medium">
                                {{ item.name }}
                            </div>
                            <span
                                class="mt-1 inline-flex rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >
                                {{
                                    typeLabel[item.pricing_type] ??
                                    item.pricing_type
                                }}
                            </span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="
                                    item.is_active
                                        ? 'bg-emerald-500/10 text-emerald-600'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                    >
                                        <MoreVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link
                                            :href="`/sports/packages/${item.id}/edit`"
                                        >
                                            <Pencil class="size-4" /> Edit
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @click="removePackage(item)"
                                    >
                                        <Trash2 class="size-4" /> Hapus
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                    <p
                        v-if="item.description"
                        class="line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ item.description }}
                    </p>
                    <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
                        <span class="font-semibold text-primary">{{
                            formatPrice(item.price)
                        }}</span>
                        <span
                            v-if="
                                formatDuration(
                                    item.duration_value,
                                    item.duration_unit,
                                )
                            "
                            class="text-xs text-muted-foreground"
                        >
                            ·
                            {{
                                formatDuration(
                                    item.duration_value,
                                    item.duration_unit,
                                )
                            }}
                        </span>
                        <span
                            v-if="item.session_count"
                            class="text-xs text-muted-foreground"
                            >· {{ item.session_count }} sesi</span
                        >
                        <span
                            v-if="item.is_promo"
                            class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600"
                        >
                            <span class="size-1.5 rounded-full bg-amber-500" />
                            Promo
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b p-4">
                <div class="flex items-center gap-2">
                    <Boxes class="size-4 text-primary" />
                    <h2 class="font-semibold">Resource / Lapangan</h2>
                </div>
                <Button size="sm" as-child>
                    <Link
                        :href="`/sports/resources/create?sport_id=${props.sport.id}`"
                    >
                        <Plus class="mr-2 size-4" /> Tambah lapangan
                    </Link>
                </Button>
            </div>
            <div
                v-if="props.sport.resources.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                Belum ada resource untuk olahraga ini.
            </div>
            <div v-else class="grid gap-4 p-4 md:grid-cols-3">
                <div
                    v-for="item in props.sport.resources"
                    :key="item.id"
                    class="overflow-hidden rounded-2xl border bg-card shadow-sm"
                >
                    <div
                        class="relative flex aspect-[16/9] items-center justify-center bg-gradient-to-br from-primary/10 via-primary/5 to-secondary/15"
                    >
                        <div
                            class="flex size-12 items-center justify-center rounded-2xl bg-background/70 text-primary shadow-sm"
                        >
                            <MapPin class="size-6" />
                        </div>
                        <span
                            class="absolute top-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-background/80 px-2.5 py-1 text-xs font-medium backdrop-blur"
                            :class="
                                item.is_active
                                    ? 'text-emerald-600'
                                    : 'text-muted-foreground'
                            "
                        >
                            <span
                                class="size-1.5 rounded-full"
                                :class="
                                    item.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-muted-foreground/60'
                                "
                            />
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="space-y-1.5 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div class="truncate font-medium">
                                {{ item.name }}
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="-mr-2 size-8 shrink-0"
                                    >
                                        <MoreVertical class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link
                                            :href="`/sports/resources/${item.id}/edit`"
                                        >
                                            <Pencil class="size-4" /> Edit
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @click="removeResource(item)"
                                    >
                                        <Trash2 class="size-4" /> Hapus
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Users class="size-3.5" />
                            {{
                                item.capacity
                                    ? `Kapasitas ${item.capacity} orang`
                                    : 'Kapasitas belum diisi'
                            }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
