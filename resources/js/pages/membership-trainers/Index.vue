<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
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
    schedulePackages: { id: number; label: string }[];
    sessionSchedules: { id:number; member:string|null; trainer:string|null; package:string|null; date:string; start_time:string; end_time:string; member_attended:boolean; trainer_attended:boolean; status:string }[];
    filters: { search: string };
    stats: {
        active_trainers: number;
        membership_ended: number;
        membership_active: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const scheduleForm = useForm({ member_trainer_package_id: '', session_date: '', start_time: '09:00', end_time: '10:00' });
function createSchedule() { scheduleForm.post('/membership-trainers/schedules', { onSuccess: () => scheduleForm.reset('session_date') }); }

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

        <section class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
            <form class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm" @submit.prevent="createSchedule">
                <div>
                    <h2 class="font-semibold">Buat jadwal sesi</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Pilih paket trainer aktif, lalu tentukan tanggal dan jam latihan.</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-sm font-medium">Member · Trainer · Paket</label>
                    <select v-model="scheduleForm.member_trainer_package_id" required class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="" disabled>Pilih paket trainer</option>
                        <option v-for="item in schedulePackages" :key="item.id" :value="String(item.id)">{{ item.label }}</option>
                    </select>
                    <p v-if="scheduleForm.errors.member_trainer_package_id" class="text-xs text-destructive">{{ scheduleForm.errors.member_trainer_package_id }}</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-sm font-medium">Tanggal sesi</label>
                    <input v-model="scheduleForm.session_date" type="date" required class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    <p v-if="scheduleForm.errors.session_date" class="text-xs text-destructive">{{ scheduleForm.errors.session_date }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5"><label class="text-sm font-medium">Mulai</label><input v-model="scheduleForm.start_time" type="time" required class="w-full rounded-md border bg-background px-3 py-2 text-sm" /></div>
                    <div class="space-y-1.5"><label class="text-sm font-medium">Selesai</label><input v-model="scheduleForm.end_time" type="time" required class="w-full rounded-md border bg-background px-3 py-2 text-sm" /></div>
                </div>
                <p v-if="scheduleForm.errors.start_time || scheduleForm.errors.end_time" class="text-xs text-destructive">{{ scheduleForm.errors.start_time || scheduleForm.errors.end_time }}</p>
                <Button type="submit" :disabled="scheduleForm.processing || !schedulePackages.length" class="w-full">Simpan jadwal</Button>
                <p v-if="!schedulePackages.length" class="text-xs text-muted-foreground">Belum ada paket trainer aktif yang bisa dijadwalkan.</p>
            </form>
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4"><h2 class="font-semibold">Jadwal sesi terbaru</h2><p class="mt-1 text-xs text-muted-foreground">Kuota baru berkurang setelah member dan trainer sama-sama mengonfirmasi hadir.</p></div>
                <div v-if="sessionSchedules.length" class="divide-y">
                    <article v-for="item in sessionSchedules" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div><p class="font-semibold">{{ item.member || 'Member' }} <span class="font-normal text-muted-foreground">· {{ item.trainer || 'Trainer' }}</span></p><p class="mt-1 text-xs text-muted-foreground">{{ item.package }} · {{ formatDate(item.date) }} · {{ item.start_time }}–{{ item.end_time }}</p></div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full px-2.5 py-1" :class="item.member_attended ? 'bg-emerald-500/10 text-emerald-700' : 'bg-muted text-muted-foreground'">Member {{ item.member_attended ? 'hadir' : 'belum' }}</span>
                            <span class="rounded-full px-2.5 py-1" :class="item.trainer_attended ? 'bg-emerald-500/10 text-emerald-700' : 'bg-muted text-muted-foreground'">Trainer {{ item.trainer_attended ? 'hadir' : 'belum' }}</span>
                            <span class="rounded-full bg-muted px-2.5 py-1 capitalize">{{ item.status === 'completed' ? 'Selesai' : item.status === 'scheduled' ? 'Terjadwal' : item.status }}</span>
                        </div>
                    </article>
                </div>
                <div v-else class="p-8 text-center text-sm text-muted-foreground">Belum ada jadwal sesi yang dibuat.</div>
            </div>
        </section>

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
