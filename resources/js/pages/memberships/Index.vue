<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    EllipsisVertical,
    Eye,
    Pencil,
    RefreshCw,
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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type MembershipStatus = 'active' | 'expired' | 'cancelled';
type Membership = {
    id: number;
    member_name: string | null;
    package_name: string | null;
    start_date: string | null;
    end_date: string | null;
    days_left: number;
    status: MembershipStatus;
    duration_days: number | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    memberships: {
        data: Membership[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string; status: string };
    stats: { active: number; expiring: number; expired: number };
};

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'expired', label: 'Kedaluwarsa' },
    { value: 'cancelled', label: 'Dibatalkan' },
];
const methodOptions = [
    { value: 'cash', label: 'Tunai' },
    { value: 'bank_transfer', label: 'Transfer Bank' },
    { value: 'qris', label: 'QRIS' },
];

const renewing = ref<Membership | null>(null);
const renewKey = ref(0);

const editing = ref<Membership | null>(null);
const editKey = ref(0);
const editStart = ref('');
const editEnd = ref('');

const statCards = computed(() => [
    {
        label: 'Langganan aktif',
        value: props.stats.active,
        icon: BadgeCheck,
        accent: 'emerald' as const,
    },
    {
        label: 'Segera berakhir',
        value: props.stats.expiring,
        icon: CalendarClock,
        accent: 'amber' as const,
    },
    {
        label: 'Kedaluwarsa',
        value: props.stats.expired,
        icon: RefreshCw,
        accent: 'rose' as const,
    },
]);

const statusMeta: Record<
    MembershipStatus,
    { label: string; badge: string; dot: string }
> = {
    active: {
        label: 'Aktif',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    },
    expired: {
        label: 'Kedaluwarsa',
        badge: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        dot: 'bg-rose-500',
    },
    cancelled: {
        label: 'Dibatalkan',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
    },
};

function openRenew(membership: Membership) {
    renewing.value = membership;
    renewKey.value++;
}

function closeRenew() {
    renewing.value = null;
}

function canRenew(membership: Membership) {
    return membership.status === 'active' && membership.days_left <= 7;
}

function openEdit(membership: Membership) {
    editing.value = membership;
    editStart.value = membership.start_date ?? '';
    editEnd.value = membership.end_date ?? '';
    editKey.value++;
}

function closeEdit() {
    editing.value = null;
}

function onStartInput() {
    if (!editing.value || !editStart.value) {
        return;
    }

    editEnd.value = addDays(editStart.value, editing.value.duration_days ?? 30);
}

function onEndInput() {
    if (!editing.value || !editEnd.value) {
        return;
    }

    editStart.value = addDays(
        editEnd.value,
        -(editing.value.duration_days ?? 30),
    );
}

function addDays(dateString: string, days: number) {
    const date = new Date(`${dateString}T00:00:00`);
    date.setDate(date.getDate() + days);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function filter() {
    router.get(
        '/memberships',
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
</script>

<template>
    <Head title="Membership" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Member"
            title="Membership"
            description="Pantau langganan member dan perpanjang masa aktifnya."
        />

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari nama member..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 font-medium">Paket</th>
                            <th class="px-4 py-3 font-medium">Periode</th>
                            <th class="px-4 py-3 font-medium">Sisa</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="row in props.memberships.data"
                            :key="row.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ row.member_name || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ row.package_name || '—' }}</div>
                                <div
                                    v-if="row.duration_days"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ row.duration_days }} hari
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(row.start_date) }} –
                                {{ formatDate(row.end_date) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="row.status === 'active'"
                                    class="font-medium"
                                    :class="
                                        row.days_left <= 7
                                            ? 'text-amber-600 dark:text-amber-400'
                                            : ''
                                    "
                                >
                                    {{ row.days_left }} hari
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statusMeta[row.status].badge"
                                >
                                    <span
                                        class="size-1.5 rounded-full"
                                        :class="statusMeta[row.status].dot"
                                    />
                                    {{ statusMeta[row.status].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger :as-child="true">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Aksi"
                                                class="hover:bg-primary/10 hover:text-primary"
                                            >
                                                <EllipsisVertical
                                                    class="size-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-48"
                                        >
                                            <DropdownMenuItem :as-child="true">
                                                <Link
                                                    :href="`/memberships/${row.id}`"
                                                    class="w-full cursor-pointer"
                                                >
                                                    <Eye class="size-4" />
                                                    Detail
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                :disabled="!canRenew(row)"
                                                @click="openRenew(row)"
                                            >
                                                <RefreshCw class="size-4" />
                                                Perpanjang
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                @click="openEdit(row)"
                                            >
                                                <Pencil class="size-4" />
                                                Ubah tanggal
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.memberships.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <BadgeCheck
                                    class="mx-auto size-10 text-muted-foreground/50"
                                />
                                <p class="mt-3 font-medium">
                                    Belum ada langganan
                                </p>
                                <p class="mt-1 text-sm">
                                    Jual paket membership lewat menu Kasir.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="props.memberships.last_page > 1"
                class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ props.memberships.current_page }} dari
                    {{ props.memberships.last_page }} ·
                    {{ props.memberships.total }} langganan
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in props.memberships.links"
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
        v-if="renewing"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeRenew"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Perpanjang membership
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">
                        {{ renewing.member_name }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ renewing.package_name }} ·
                        {{ renewing.duration_days ?? 30 }} hari
                    </p>
                </div>
                <Button variant="ghost" size="icon" @click="closeRenew"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="renewKey"
                :action="`/memberships/${renewing.id}/renew`"
                method="post"
                class="space-y-5 p-5"
                @success="closeRenew"
                v-slot="{ errors, processing }"
            >
                <p class="rounded-xl bg-muted/40 p-3 text-sm">
                    Masa aktif ditambahkan dari
                    <span class="font-medium">
                        {{
                            renewing.days_left > 0
                                ? formatDate(renewing.end_date)
                                : 'hari ini'
                        }}
                    </span>
                    selama {{ renewing.duration_days ?? 30 }} hari.
                </p>

                <div class="grid gap-2">
                    <Label>Metode pembayaran</Label>
                    <FormSelect
                        name="payment_method"
                        :options="methodOptions"
                        default-value="cash"
                    />
                    <p
                        v-if="errors.payment_method"
                        class="text-xs text-destructive"
                    >
                        {{ errors.payment_method }}
                    </p>
                </div>

                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeRenew"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Memproses...' : 'Perpanjang' }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>

    <div
        v-if="editing"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeEdit"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Ubah tanggal membership
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">
                        {{ editing.member_name }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ editing.package_name }} ·
                        {{ editing.duration_days ?? 30 }} hari
                    </p>
                </div>
                <Button variant="ghost" size="icon" @click="closeEdit"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="editKey"
                :action="`/memberships/${editing.id}`"
                method="put"
                class="space-y-5 p-5"
                @success="closeEdit"
                v-slot="{ errors, processing }"
            >
                <p
                    class="rounded-xl bg-muted/40 p-3 text-xs text-muted-foreground"
                >
                    Ubah salah satu tanggal, tanggal lainnya dihitung otomatis
                    sesuai masa aktif paket ({{ editing.duration_days ?? 30 }}
                    hari).
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit-start">Tanggal aktivasi</Label>
                        <Input
                            id="edit-start"
                            name="start_date"
                            type="date"
                            v-model="editStart"
                            @change="onStartInput"
                        />
                        <p
                            v-if="errors.start_date"
                            class="text-xs text-destructive"
                        >
                            {{ errors.start_date }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-end">Tanggal expired</Label>
                        <Input
                            id="edit-end"
                            name="end_date"
                            type="date"
                            v-model="editEnd"
                            @change="onEndInput"
                        />
                        <p
                            v-if="errors.end_date"
                            class="text-xs text-destructive"
                        >
                            {{ errors.end_date }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeEdit"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
