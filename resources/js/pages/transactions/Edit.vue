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
        payment_method: string | null;
        payment_status: string;
        reference: string;
    };
};

const props = defineProps<Props>();
const form = useForm({
    customer_name: props.transaction.customer_name ?? '',
    amount: String(props.transaction.amount),
    payment_method: props.transaction.payment_method ?? 'cash',
    payment_status: props.transaction.payment_status,
});

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
            description="Perbarui informasi pelanggan dan pembayaran transaksi."
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
                    <label for="amount" class="text-sm font-medium">Jumlah transaksi (Rp)</label>
                    <input id="amount" v-model="form.amount" type="number" min="0" step="1" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                    <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                </div>

                <div class="space-y-2">
                    <label for="payment_method" class="text-sm font-medium">Metode pembayaran</label>
                    <select id="payment_method" v-model="form.payment_method" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring">
                        <option value="cash">Tunai</option>
                        <option value="bank_transfer">Transfer bank</option>
                        <option value="qris">QRIS</option>
                    </select>
                    <p v-if="form.errors.payment_method" class="text-sm text-destructive">{{ form.errors.payment_method }}</p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <label for="payment_status" class="text-sm font-medium">Status pembayaran</label>
                    <select id="payment_status" v-model="form.payment_status" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring">
                        <option value="pending">Pending</option>
                        <option value="partial">DP / Sebagian</option>
                        <option value="paid">Lunas</option>
                        <option value="failed">Gagal</option>
                        <option value="refunded">Refund</option>
                    </select>
                    <p v-if="form.errors.payment_status" class="text-sm text-destructive">{{ form.errors.payment_status }}</p>
                    <p class="text-xs text-muted-foreground">Pastikan status sesuai kondisi pembayaran yang sebenarnya.</p>
                </div>
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
