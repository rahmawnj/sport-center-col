<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';

type Props = {
    transaction: {
        id: number;
        invoice_number: string | null;
        customer_name: string | null;
        amount: number;
        paid_amount: number;
        payment_method: string | null;
        payment_review_status: 'pending' | 'accepted' | 'rejected';
        reference: string;
    };
};

const props = defineProps<Props>();
const form = useForm({
    customer_name: props.transaction.customer_name ?? '',
    amount: String(props.transaction.amount),
    paid_amount: String(props.transaction.paid_amount),
    payment_method: props.transaction.payment_method ?? 'cash',
    payment_review_status: props.transaction.payment_review_status,
});

const remainingAmount = () => Math.max(0, Number(form.amount || 0) - Number(form.paid_amount || 0));
const formatCurrency = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(value);

function submit() {
    form.put(`/transactions/${props.transaction.id}`);
}
</script>

<template>
    <Head :title="`Ubah Transaksi #${transaction.id}`" />
    <div class="space-y-6 p-4 md:p-6">
        <Link href="/transactions" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="size-4" /> Kembali ke transaksi
        </Link>

        <PageHeader
            eyebrow="Keuangan"
            :title="`Ubah Transaksi #${transaction.id}`"
            description="Kelola tagihan, catat nominal yang dibayar, dan verifikasi penerimaan uang secara terpisah."
        />

        <form @submit.prevent="submit" class="max-w-3xl space-y-6 rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
            <div class="rounded-xl bg-muted/40 p-4">
                <p class="text-xs text-muted-foreground">Nomor invoice</p>
                <p class="mt-1 font-mono font-semibold">{{ transaction.invoice_number || transaction.reference }}</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <label for="customer_name" class="text-sm font-medium">Nama pelanggan</label>
                    <input id="customer_name" v-model="form.customer_name" type="text" maxlength="255" placeholder="Nama pelanggan" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                    <p v-if="form.errors.customer_name" class="text-sm text-destructive">{{ form.errors.customer_name }}</p>
                </div>

                <div class="space-y-2">
                    <label for="amount" class="text-sm font-medium">Total tagihan (Rp)</label>
                    <input id="amount" v-model="form.amount" type="number" min="0" step="1" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                    <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                </div>

                <div class="space-y-2">
                    <label for="payment_method" class="text-sm font-medium">Metode pembayaran</label>
                    <select id="payment_method" v-model="form.payment_method" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring">
                        <option value="cash">Tunai</option>
                        <option value="bank_transfer">Transfer bank</option>
                    </select>
                    <p v-if="form.errors.payment_method" class="text-sm text-destructive">{{ form.errors.payment_method }}</p>
                </div>

                <div class="space-y-2">
                    <label for="paid_amount" class="text-sm font-medium">Nominal yang dibayarkan (Rp)</label>
                    <input id="paid_amount" v-model="form.paid_amount" type="number" min="0" :max="form.amount || undefined" step="1" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                    <p v-if="form.errors.paid_amount" class="text-sm text-destructive">{{ form.errors.paid_amount }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Sisa tagihan</label>
                    <div class="rounded-lg border bg-muted/30 px-3 py-2.5 text-sm font-semibold tabular-nums">{{ formatCurrency(remainingAmount()) }}</div>
                    <p class="text-xs text-muted-foreground">Dihitung dari total tagihan dikurangi nominal yang dibayarkan.</p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <label for="payment_review_status" class="text-sm font-medium">Verifikasi pembayaran</label>
                    <select id="payment_review_status" v-model="form.payment_review_status" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring">
                        <option value="pending">Menunggu pemeriksaan</option>
                        <option value="accepted">Pembayaran diterima</option>
                        <option value="rejected">Pembayaran ditolak</option>
                    </select>
                    <p v-if="form.errors.payment_review_status" class="text-sm text-destructive">{{ form.errors.payment_review_status }}</p>
                    <p class="text-xs text-muted-foreground">Status ini adalah keputusan admin setelah uang atau bukti pembayaran diperiksa. Metode transfer/tunai tidak otomatis berarti pembayaran diterima.</p>
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-4 text-sm text-sky-900">
                <p class="font-semibold">Dampak verifikasi</p>
                <p class="mt-1">DP yang diterima dapat mengonfirmasi booking. Membership dan paket trainer baru aktif setelah pembayaran diterima dan lunas.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end">
                <Button type="button" variant="outline" as-child>
                    <Link href="/transactions">Batal</Link>
                </Button>
                <Button type="submit" :disabled="form.processing" class="gap-2">
                    <Save class="size-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </Button>
            </div>
        </form>
    </div>
</template>
