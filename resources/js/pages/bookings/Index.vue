<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { CalendarDays, Check, Clock3, CreditCard, Eye, Search, XCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Booking {
    id: number;
    booking_code: string;
    guest_name: string | null;
    booking_status: string;
    payment_status: string;
    payment_method: string;
    total_amount: number;
    created_at: string;
    details: Array<{ zone_space: string | null; zone: string | null; start_time: string; end_time: string }>;
    payment_proofs: Array<{ id: number; payment_option: string; amount_paid: number; proof_url: string | null }>;
}
interface LinkItem { url: string | null; label: string; active: boolean }
interface Props {
    bookings: { data: Booking[]; current_page: number; last_page: number; total: number; links: LinkItem[] };
    filters: { search: string; status: string; payment_status: string };
    stats: { pending: number; approved: number; payment_pending: number; total: number };
}

const props = defineProps<Props>();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const paymentStatus = ref(props.filters.payment_status ?? 'all');
const selected = ref<Booking | null>(null);

const filter = () => router.get('/bookings', { search: search.value || undefined, status: status.value, payment_status: paymentStatus.value }, { preserveState: true, replace: true });
const setBookingStatus = (booking: Booking, value: string) => {
    if (!window.confirm(`Ubah status booking ${booking.booking_code} menjadi ${value}?`)) return;
    router.put(`/bookings/${booking.id}/status`, { booking_status: value }, { preserveScroll: true });
};
const setPaymentStatus = (booking: Booking, value: string) => {
    if (!window.confirm(`Ubah status pembayaran ${booking.booking_code}?`)) return;
    router.put(`/bookings/${booking.id}/payment-status`, { payment_status: value }, { preserveScroll: true });
};
const formatMoney = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const formatDateTime = (value: string) => new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
const bookingLabel = (value: string) => ({ pending: 'Menunggu Approval', approved: 'Approved', rejected: 'Ditolak', cancelled: 'Dibatalkan' }[value] ?? value);
const paymentLabel = (value: string) => ({ unpaid: 'Belum Bayar', dp_paid: 'DP 50%', fully_paid: 'Lunas' }[value] ?? value);
</script>

<template>
<Head title="Bookings" />
<div class="space-y-6 p-4 md:p-6">
    <div>
        <p class="text-sm font-medium text-primary">Operations</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Booking</h1>
        <p class="mt-1 text-sm text-muted-foreground">Lihat booking baru, approve booking, dan verifikasi status pembayaran.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Booking Baru</span><Clock3 class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.pending }}</div></div>
        <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Approved</span><Check class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.approved }}</div></div>
        <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Pembayaran Belum Lunas</span><CreditCard class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.payment_pending }}</div></div>
        <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Total Booking</span><CalendarDays class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.total }}</div></div>
    </div>

    <div class="rounded-2xl border bg-card shadow-sm">
        <div class="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" @keyup.enter="filter" class="pl-9" placeholder="Cari kode booking / nama..." /></div>
            <select v-model="status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="all">Semua booking</option><option value="pending">Menunggu approval</option><option value="approved">Approved</option><option value="rejected">Ditolak</option><option value="cancelled">Dibatalkan</option></select>
            <select v-model="paymentStatus" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="all">Semua pembayaran</option><option value="unpaid">Belum bayar</option><option value="dp_paid">DP 50%</option><option value="fully_paid">Lunas</option></select>
            <Button @click="filter">Filter</Button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-5 py-3 font-medium">Booking</th><th class="px-5 py-3 font-medium">Jadwal</th><th class="px-5 py-3 font-medium">Booking Status</th><th class="px-5 py-3 font-medium">Pembayaran</th><th class="px-5 py-3 text-right font-medium">Action</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="booking in props.bookings.data" :key="booking.id" class="hover:bg-muted/20">
                        <td class="px-5 py-4"><div class="font-medium">{{ booking.booking_code }}</div><div class="text-xs text-muted-foreground">{{ booking.guest_name || 'Guest' }}</div><div class="mt-1 text-xs text-muted-foreground">{{ booking.details[0]?.zone }} · {{ booking.details[0]?.zone_space }}</div></td>
                        <td class="px-5 py-4 text-muted-foreground">{{ booking.details[0] ? formatDateTime(booking.details[0].start_time) : '—' }}<div v-if="booking.details[0]" class="text-xs">s/d {{ formatDateTime(booking.details[0].end_time) }}</div></td>
                        <td class="px-5 py-4"><span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ bookingLabel(booking.booking_status) }}</span></td>
                        <td class="px-5 py-4"><div class="font-medium">{{ formatMoney(booking.total_amount) }}</div><div class="text-xs text-muted-foreground">{{ paymentLabel(booking.payment_status) }}</div></td>
                        <td class="px-5 py-4"><div class="flex justify-end gap-1"><Button variant="ghost" size="icon" title="Detail" @click="selected = booking"><Eye class="size-4" /></Button><Button v-if="booking.booking_status === 'pending'" variant="ghost" size="icon" title="Approve" @click="setBookingStatus(booking, 'approved')"><Check class="size-4" /></Button><Button v-if="booking.booking_status === 'pending'" variant="ghost" size="icon" title="Tolak" @click="setBookingStatus(booking, 'rejected')"><XCircle class="size-4 text-destructive" /></Button></div></td>
                    </tr>
                    <tr v-if="props.bookings.data.length === 0"><td colspan="5" class="px-5 py-16 text-center text-muted-foreground">Belum ada booking.</td></tr>
                </tbody>
            </table>
        </div>
        <div v-if="props.bookings.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-muted-foreground">Page {{ props.bookings.current_page }} of {{ props.bookings.last_page }} · {{ props.bookings.total }} booking</p><div class="flex flex-wrap gap-1"><a v-for="link in props.bookings.links" :key="link.label" :href="link.url ?? '#'" @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })"><Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" v-html="link.label" /></a></div></div>
    </div>
</div>

<div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="selected = null">
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border bg-background shadow-2xl">
        <div class="flex items-start justify-between border-b p-5"><div><p class="text-sm font-medium text-primary">Booking Detail</p><h2 class="mt-1 text-xl font-semibold">{{ selected.booking_code }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ selected.guest_name || 'Guest' }}</p></div><Button variant="ghost" size="icon" @click="selected = null"><XCircle class="size-4" /></Button></div>
        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <div class="space-y-3"><div><p class="text-xs text-muted-foreground">Venue</p><p class="font-medium">{{ selected.details[0]?.zone }} · {{ selected.details[0]?.zone_space }}</p></div><div><p class="text-xs text-muted-foreground">Jadwal</p><p class="font-medium">{{ selected.details[0] ? formatDateTime(selected.details[0].start_time) : '—' }}</p></div><div><p class="text-xs text-muted-foreground">Total</p><p class="font-medium">{{ formatMoney(selected.total_amount) }}</p></div></div>
            <div class="space-y-4"><div><p class="mb-2 text-xs text-muted-foreground">Booking Status</p><select :value="selected.booking_status" @change="setBookingStatus(selected!, ($event.target as HTMLSelectElement).value)" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="pending">Menunggu approval</option><option value="approved">Approved</option><option value="rejected">Ditolak</option><option value="cancelled">Dibatalkan</option></select></div><div><p class="mb-2 text-xs text-muted-foreground">Payment Status</p><select :value="selected.payment_status" @change="setPaymentStatus(selected!, ($event.target as HTMLSelectElement).value)" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="unpaid">Belum bayar</option><option value="dp_paid">DP 50%</option><option value="fully_paid">Lunas</option></select></div></div>
            <div class="sm:col-span-2"><p class="mb-2 text-xs text-muted-foreground">Bukti Pembayaran</p><div v-if="selected.payment_proofs.length" class="grid gap-3 sm:grid-cols-2"> <a v-for="proof in selected.payment_proofs" :key="proof.id" :href="proof.proof_url || '#'" target="_blank" class="rounded-xl border p-3"><img v-if="proof.proof_url" :src="proof.proof_url" alt="Bukti pembayaran" class="max-h-64 w-full rounded-lg object-contain" /><div class="mt-2 text-xs text-muted-foreground">{{ paymentLabel(selected.payment_status) }} · {{ formatMoney(proof.amount_paid) }}</div></a></div><p v-else class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">Tidak ada bukti pembayaran karena booking belum melakukan pembayaran.</p></div>
        </div>
    </div>
</div>
</template>
