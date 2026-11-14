<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Clock3, Dumbbell, Pencil, Wallet, Users, CheckCircle2, Building2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

type PackageHistory = {
    id: number;
    member_name: string;
    package_name: string;
    facility_name: string;
    start_date: string | null;
    expires_at: string | null;
    status: string;
    total_sessions: number;
    completed_sessions: number;
    share_percentage: number;
    earned_amount: number;
};

type Trainer = {
    id: number;
    name: string;
    specialty: string | null;
    created_at: string | null;
    updated_at: string | null;
    user_name: string | null;
    user_email: string | null;
    facilities: string[];
    stats: { packages_count: number; completed_sessions: number; earned_total: number };
    packages: PackageHistory[];
};

const props = defineProps<{ trainer: Trainer }>();

function date(value: string | null) {
    return value
        ? new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value))
        : '—';
}
function money(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}
function initials(value: string) {
    return value.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
}
function statusLabel(value: string) {
    return ({ active: 'Aktif', pending: 'Menunggu', completed: 'Selesai', expired: 'Kedaluwarsa', cancelled: 'Dibatalkan' } as Record<string, string>)[value] ?? value;
}
function statusClass(value: string) {
    if (value === 'active') return 'bg-emerald-500/10 text-emerald-600';
    if (value === 'pending') return 'bg-amber-500/10 text-amber-600';
    return 'bg-muted text-muted-foreground';
}
</script>

<template>
    <Head :title="`${props.trainer.name} · Trainer`" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" as-child><Link href="/trainers"><ArrowLeft class="size-4" /></Link></Button>
            <div><p class="text-sm text-muted-foreground">Trainers / Detail</p><h1 class="text-xl font-semibold">Detail trainer</h1></div>
        </div>

        <section class="overflow-hidden rounded-3xl border bg-card shadow-sm">
            <div class="h-28 bg-gradient-to-r from-primary/15 via-primary/5 to-transparent" />
            <div class="px-5 pb-6 md:px-7">
                <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-end gap-4">
                        <div class="flex size-20 items-center justify-center rounded-2xl border-4 border-background bg-primary/10 text-xl font-bold text-primary shadow-sm">{{ initials(props.trainer.name) }}</div>
                        <div class="pb-1">
                            <div class="flex flex-wrap items-center gap-2"><h2 class="text-2xl font-semibold">{{ props.trainer.name }}</h2><span v-if="props.trainer.specialty" class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ props.trainer.specialty }}</span></div>
                            <p class="mt-1 text-sm text-muted-foreground">Trainer #{{ props.trainer.id }} · Dibuat {{ date(props.trainer.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2"><Button variant="outline" as-child><Link href="/trainers">Kembali</Link></Button><Button as-child><Link :href="`/trainers?edit=${props.trainer.id}`"><Pencil class="mr-2 size-4" /> Edit trainer</Link></Button></div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-2xl border bg-card p-5 shadow-sm"><div class="flex items-center justify-between text-sm text-muted-foreground">Total paket terjual <Dumbbell class="size-4" /></div><p class="mt-3 text-2xl font-semibold tabular-nums">{{ props.trainer.stats.packages_count }}</p></section>
            <section class="rounded-2xl border bg-card p-5 shadow-sm"><div class="flex items-center justify-between text-sm text-muted-foreground">Sesi berhasil <CheckCircle2 class="size-4" /></div><p class="mt-3 text-2xl font-semibold tabular-nums">{{ props.trainer.stats.completed_sessions }}</p></section>
            <section class="rounded-2xl border bg-card p-5 shadow-sm sm:col-span-2"><div class="flex items-center justify-between text-sm text-muted-foreground">Akumulasi bagian trainer <Wallet class="size-4" /></div><p class="mt-3 text-2xl font-semibold tabular-nums text-emerald-600">{{ money(props.trainer.stats.earned_total) }}</p><p class="mt-1 text-xs text-muted-foreground">Dihitung dari sesi selesai yang dikonfirmasi member dan trainer.</p></section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2"><Dumbbell class="size-4 text-primary" /><h3 class="font-semibold">Informasi trainer</h3></div>
                <div class="mt-5 space-y-5">
                    <div><p class="text-xs text-muted-foreground">Nama</p><p class="mt-1 text-sm font-medium">{{ props.trainer.name }}</p></div>
                    <div><p class="text-xs text-muted-foreground">Email akun</p><p class="mt-1 text-sm font-medium">{{ props.trainer.user_email || 'Belum terhubung' }}</p></div>
                    <div><p class="text-xs text-muted-foreground">Spesialisasi</p><p class="mt-1 text-sm font-medium">{{ props.trainer.specialty || '—' }}</p></div>
                    <div><p class="text-xs text-muted-foreground">Olahraga / fasilitas</p><div class="mt-2 flex flex-wrap gap-2"><span v-for="facility in props.trainer.facilities" :key="facility" class="rounded-full bg-muted px-3 py-1 text-xs font-medium">{{ facility }}</span><span v-if="!props.trainer.facilities.length" class="text-sm">—</span></div></div>
                    <div class="border-t pt-4"><p class="text-xs text-muted-foreground">Terakhir diperbarui</p><p class="mt-1 text-sm font-medium">{{ date(props.trainer.updated_at) }}</p></div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="border-b p-5"><h3 class="font-semibold">Riwayat pembagian keuntungan</h3><p class="mt-1 text-sm text-muted-foreground">Rincian bagian trainer dari setiap paket personal trainer yang dibeli member.</p></div>
                <div v-if="props.trainer.packages.length" class="divide-y">
                    <div v-for="item in props.trainer.packages" :key="item.id" class="space-y-3 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div><p class="font-semibold">{{ item.package_name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.member_name }} · {{ item.facility_name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ date(item.start_date) }} – {{ date(item.expires_at) }}</p></div>
                            <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                        </div>
                        <div class="grid gap-3 rounded-xl bg-muted/40 p-3 sm:grid-cols-3">
                            <div><p class="text-xs text-muted-foreground">Sesi selesai</p><p class="mt-1 text-sm font-semibold tabular-nums">{{ item.completed_sessions }} / {{ item.total_sessions }}</p></div>
                            <div><p class="text-xs text-muted-foreground">Bagian trainer</p><p class="mt-1 text-sm font-semibold">{{ item.share_percentage }}%</p></div>
                            <div><p class="text-xs text-muted-foreground">Akumulasi diterima</p><p class="mt-1 text-sm font-semibold text-emerald-600">{{ money(item.earned_amount) }}</p></div>
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-14 text-center"><Users class="mx-auto size-9 text-muted-foreground/40" /><p class="mt-3 font-medium">Belum ada paket trainer</p><p class="mt-1 text-sm text-muted-foreground">Riwayat pembelian dan pembagian keuntungan akan muncul di sini.</p></div>
            </section>
        </div>
    </div>
</template>