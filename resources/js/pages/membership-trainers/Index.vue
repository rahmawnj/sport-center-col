<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, BadgeCheck, CalendarDays, Dumbbell, Search, Users } from '@lucide/vue';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type MembershipStatus = 'active' | 'expired' | 'upcoming' | 'none';
type TrainerRow = {
    id: number;
    member_name: string | null;
    trainer_name: string | null;
    trainer_package: string | null;
    facility_name: string | null;
    trainer_start: string | null;
    trainer_end: string | null;
    remaining_sessions: number;
    total_sessions: number;
    membership_package: string | null;
    membership_end: string | null;
    membership_status: MembershipStatus;
};
type LinkItem = { url: string | null; label: string; active: boolean };
const props = defineProps<{
    rows: {
        data: TrainerRow[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string };
    stats: {
        active_trainers: number;
        membership_ended: number;
        membership_active: number;
    };
}>();

const search = ref(props.filters.search ?? '');

function filter() {
    router.get('/membership-trainers', { search: search.value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

function reset() {
    search.value = '';
    filter();
}

function formatDate(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

const membershipMeta: Record<MembershipStatus, { label: string; className: string }> = {
    active: {
        label: 'Membership aktif',
        className: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    },
    expired: {
        label: 'Membership habis',
        className: 'bg-rose-500/10 text-rose-700 dark:text-rose-400',
    },
    upcoming: {
        label: 'Belum mulai',
        className: 'bg-sky-500/10 text-sky-700 dark:text-sky-400',
    },
    none: {
        label: 'Tidak punya membership',
        className: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
    },
};
</script>

<template>
    <Head title="Membership Trainer" />

    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Monitoring Member"
            title="Membership Trainer"
            description="Pantau member yang paket personal trainernya masih aktif dan bandingkan dengan masa membership pada olahraga yang sama."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">Trainer aktif</p>
                    <Dumbbell class="size-5 text-primary" />
                </div>
                <p class="mt-3 text-3xl font-semibold tabular-nums">{{ stats.active_trainers }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Paket trainer sedang berjalan</p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">Membership habis / tidak ada</p>
                    <AlertTriangle class="size-5 text-rose-500" />
                </div>
                <p class="mt-3 text-3xl font-semibold tabular-nums text-rose-600 dark:text-rose-400">{{ stats.membership_ended }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Perlu ditindaklanjuti admin</p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">Membership masih aktif</p>
                    <BadgeCheck class="size-5 text-emerald-600" />
                </div>
                <p class="mt-3 text-3xl font-semibold tabular-nums">{{ stats.membership_active }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Pada olahraga yang sama</p>
            </div>
        </div>

        <div
            v-if="stats.membership_ended > 0"
            class="flex items-start gap-3 rounded-xl border border-rose-500/20 bg-rose-500/5 p-4"
        >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-rose-600" />
            <div>
                <p class="font-medium text-rose-700 dark:text-rose-400">
                    Ada {{ stats.membership_ended }} paket trainer aktif tanpa membership aktif
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Periksa daftar di bawah. Status ini dihitung berdasarkan membership pada fasilitas/olahraga yang sama dengan paket trainer.
                </p>
            </div>
        </div>

        <div class="rounded-2xl border bg-card p-4 shadow-sm">
            <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="filter">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        class="pl-9"
                        placeholder="Cari nama member, trainer, paket, atau olahraga..."
                    />
                </div>
                <div class="flex gap-2">
                    <Button type="submit">Cari</Button>
                    <Button type="button" variant="outline" @click="reset">Reset</Button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div>
                    <h2 class="font-semibold">Daftar trainer yang masih aktif</h2>
                    <p class="text-xs text-muted-foreground">Menampilkan paket yang sedang berjalan dan sesi yang belum habis.</p>
                </div>
                <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ rows.total }} data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 font-medium">Olahraga</th>
                            <th class="px-4 py-3 font-medium">Trainer & Paket</th>
                            <th class="px-4 py-3 font-medium">Periode Trainer</th>
                            <th class="px-4 py-3 font-medium">Sisa Sesi</th>
                            <th class="px-4 py-3 font-medium">Membership</th>
                            <th class="px-4 py-3 font-medium">Status Membership</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in rows.data" :key="row.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ row.member_name || '—' }}</td>
                            <td class="px-4 py-3">{{ row.facility_name || '—' }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ row.trainer_name || '—' }}</p>
                                <p class="text-xs text-muted-foreground">{{ row.trainer_package || '—' }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                {{ formatDate(row.trainer_start) }} – {{ formatDate(row.trainer_end) }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                <span class="font-semibold">{{ row.remaining_sessions }}</span>
                                <span class="text-muted-foreground"> / {{ row.total_sessions }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ row.membership_package || '—' }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ row.membership_end ? 'Berakhir ' + formatDate(row.membership_end) : 'Tidak ada riwayat membership' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="membershipMeta[row.membership_status].className"
                                >
                                    {{ membershipMeta[row.membership_status].label }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!rows.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                <Users class="mx-auto size-9 opacity-40" />
                                <p class="mt-3 font-medium">Tidak ada paket trainer aktif</p>
                                <p class="mt-1 text-xs">Data akan muncul ketika member memiliki paket trainer yang sedang berjalan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="rows.last_page > 1"
                class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ rows.current_page }} dari {{ rows.last_page }} · {{ rows.total }} paket trainer aktif
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in rows.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })"
                    >
                        <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url">
                            <span v-html="link.label" />
                        </Button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
