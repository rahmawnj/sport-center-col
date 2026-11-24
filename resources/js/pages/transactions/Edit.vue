<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { ArrowLeft, Save, ShoppingBag, CreditCard } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';

type Option = { id: number; name?: string; label?: string; price?: number; duration_days?: number; total_sessions?: number };
type Item = {
    type: string;
    title: string;
    fields: Record<string, any>;
    options: {
        packages?: Option[];
        trainer_packages?: Option[];
        courts?: Option[];
        schedules?: Option[];
    };
};
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
    item: Item;
};

const props = defineProps<Props>();
const fields = props.item.fields ?? {};
const form = useForm({
    customer_name: props.transaction.customer_name ?? '',
    amount: String(props.transaction.amount),
    paid_amount: String(props.transaction.paid_amount),
    payment_method: props.transaction.payment_method ?? 'cash',
    payment_review_status: props.transaction.payment_review_status,
    item_package_id: fields.package_id ? String(fields.package_id) : '',
    item_trainer_package_id: fields.trainer_package_id ? String(fields.trainer_package_id) : '',
    item_court_id: fields.court_id ? String(fields.court_id) : '',
    item_date: fields.date ?? '',
    item_start_time: fields.start_time ?? '',
    item_end_time: fields.end_time ?? '',
    item_class_schedule_id: fields.class_schedule_id ? String(fields.class_schedule_id) : '',
    item_session_date: fields.session_date ?? '',
});

watch(
    () => [form.item_package_id, form.item_trainer_package_id],
    () => {
        if (props.item.type === 'membership') {
            const membership = (props.item.options.packages || []).find((option) => String(option.id) === form.item_package_id);
            const trainer = (props.item.options.trainer_packages || []).find((option) => String(option.id) === form.item_trainer_package_id);
            if (membership) form.amount = String((membership.price || 0) + (fields.trainer_package_id && trainer ? trainer.price || 0 : 0));
        }
        if (props.item.type === 'trainer_package') {
            const trainer = (props.item.options.trainer_packages || []).find((option) => String(option.id) === form.item_trainer_package_id);
            if (trainer) form.amount = String(trainer.price || 0);
        }
    },
);
watch(
    () => form.item_class_schedule_id,
    (id) => {
        if (props.item.type !== 'class') return;
        const schedule = (props.item.options.schedules || []).find((option) => String(option.id) === id);
        if (schedule) form.amount = String((schedule.price || 0) * (fields.participants || []).length);
    },
);

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
            description="Periksa item yang dibeli, ubah detail pesanan, lalu kelola tagihan dan verifikasi pembayarannya."
        />

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <section class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-primary/10 p-2.5 text-primary"><ShoppingBag class="size-5" /></div>
                    <div>
                        <h2 class="font-semibold">Detail pembelian</h2>
                        <p class="text-sm text-muted-foreground">Item yang tercatat pada transaksi ini.</p>
                    </div>
                </div>

                <div class="rounded-xl bg-muted/40 p-4">
                    <p class="text-xs text-muted-foreground">Jenis transaksi</p>
                    <p class="mt-1 font-semibold">{{ item.title }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Invoice: {{ transaction.invoice_number || transaction.reference }}</p>
                </div>

                <div v-if="item.type === 'membership'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Fasilitas / olahraga</p>
                        <p class="mt-1 font-medium">{{ fields.facility_name || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Masa berlaku saat ini</p>
                        <p class="mt-1 font-medium">{{ fields.start_date || 'Menunggu aktivasi' }} <span v-if="fields.end_date">— {{ fields.end_date }}</span></p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <label for="item_package_id" class="text-sm font-medium">Paket membership</label>
                        <select id="item_package_id" v-model="form.item_package_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm">
                            <option v-for="option in item.options.packages || []" :key="option.id" :value="String(option.id)">
                                {{ option.name }} · {{ formatCurrency(option.price || 0) }} · {{ option.duration_days }} hari
                            </option>
                        </select>
                        <p v-if="form.errors.item_package_id" class="text-sm text-destructive">{{ form.errors.item_package_id }}</p>
                    </div>
                    <div v-if="fields.trainer_package_id" class="space-y-2 sm:col-span-2">
                        <label for="item_trainer_package_id" class="text-sm font-medium">Add-on paket trainer</label>
                        <select id="item_trainer_package_id" v-model="form.item_trainer_package_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm">
                            <option v-for="option in item.options.trainer_packages || []" :key="option.id" :value="String(option.id)">
                                {{ option.name }} · {{ formatCurrency(option.price || 0) }} · {{ option.total_sessions }} sesi
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">Coach saat ini: {{ fields.trainer_name || 'Belum ditentukan' }}. Penggantian paket trainer tidak diizinkan jika sesi sudah digunakan.</p>
                        <p v-if="form.errors.item_trainer_package_id" class="text-sm text-destructive">{{ form.errors.item_trainer_package_id }}</p>
                    </div>
                </div>

                <div v-else-if="item.type === 'trainer_package'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Fasilitas</p>
                        <p class="mt-1 font-medium">{{ fields.facility_name || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Coach</p>
                        <p class="mt-1 font-medium">{{ fields.trainer_name || 'Belum ditentukan' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Sesi terpakai</p>
                        <p class="mt-1 font-medium">{{ fields.used_sessions }} dari {{ fields.total_sessions }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Masa berlaku</p>
                        <p class="mt-1 font-medium">{{ fields.start_date || 'Menunggu aktivasi' }} <span v-if="fields.end_date">— {{ fields.end_date }}</span></p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <label for="item_trainer_package_id" class="text-sm font-medium">Paket trainer</label>
                        <select id="item_trainer_package_id" v-model="form.item_trainer_package_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm">
                            <option v-for="option in item.options.trainer_packages || []" :key="option.id" :value="String(option.id)">
                                {{ option.name }} · {{ formatCurrency(option.price || 0) }} · {{ option.total_sessions }} sesi
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">Paket tidak dapat diganti jika sesi sudah pernah digunakan.</p>
                        <p v-if="form.errors.item_trainer_package_id" class="text-sm text-destructive">{{ form.errors.item_trainer_package_id }}</p>
                    </div>
                </div>

                <div v-else-if="item.type === 'booking'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Fasilitas / olahraga</p>
                        <p class="mt-1 font-medium">{{ fields.facility_name || '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Add-on coach</p>
                        <p class="mt-1 font-medium">{{ fields.trainer_name || 'Tidak ada' }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="item_court_id" class="text-sm font-medium">Lapangan</label>
                        <select id="item_court_id" v-model="form.item_court_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm">
                            <option v-for="court in item.options.courts || []" :key="court.id" :value="String(court.id)">{{ court.name }}</option>
                        </select>
                        <p v-if="form.errors.item_court_id" class="text-sm text-destructive">{{ form.errors.item_court_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="item_date" class="text-sm font-medium">Tanggal booking</label>
                        <input id="item_date" v-model="form.item_date" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.item_date" class="text-sm text-destructive">{{ form.errors.item_date }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="item_start_time" class="text-sm font-medium">Jam mulai</label>
                        <input id="item_start_time" v-model="form.item_start_time" type="time" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.item_start_time" class="text-sm text-destructive">{{ form.errors.item_start_time }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="item_end_time" class="text-sm font-medium">Jam selesai</label>
                        <input id="item_end_time" v-model="form.item_end_time" type="time" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.item_end_time" class="text-sm text-destructive">{{ form.errors.item_end_time }}</p>
                    </div>
                    <p class="text-xs text-muted-foreground sm:col-span-2">Harga booking dihitung ulang dari master slot, durasi, dan penyesuaian harga lapangan saat disimpan. Slot harus berurutan dan tersedia.</p>
                </div>

                <div v-else-if="item.type === 'class'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Kelas</p>
                        <p class="mt-1 font-medium">{{ fields.class_name }} · {{ fields.facility_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Peserta</p>
                        <p class="mt-1 font-medium">{{ (fields.participants || []).join(', ') }}</p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <label for="item_class_schedule_id" class="text-sm font-medium">Jadwal kelas</label>
                        <select id="item_class_schedule_id" v-model="form.item_class_schedule_id" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm">
                            <option v-for="option in item.options.schedules || []" :key="option.id" :value="String(option.id)">{{ option.label }}</option>
                        </select>
                        <p v-if="form.errors.item_class_schedule_id" class="text-sm text-destructive">{{ form.errors.item_class_schedule_id }}</p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <label for="item_session_date" class="text-sm font-medium">Tanggal kelas</label>
                        <input id="item_session_date" v-model="form.item_session_date" type="date" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.item_session_date" class="text-sm text-destructive">{{ form.errors.item_session_date }}</p>
                    </div>
                    <p class="text-xs text-muted-foreground sm:col-span-2">Perubahan jadwal diterapkan ke seluruh peserta dalam transaksi ini dan akan memeriksa kuota jadwal tujuan.</p>
                </div>

                <div v-else class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                    Detail item untuk jenis transaksi ini belum memiliki data produk terpisah. Nominal transaksi tetap dapat diedit pada bagian pembayaran.
                </div>
            </section>

            <form @submit.prevent="submit" class="min-w-0 space-y-6 rounded-2xl border bg-card p-5 shadow-sm sm:p-7">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-primary/10 p-2.5 text-primary"><CreditCard class="size-5" /></div>
                    <div>
                        <h2 class="font-semibold">Tagihan & pembayaran</h2>
                        <p class="text-sm text-muted-foreground">Metode pembayaran terpisah dari keputusan verifikasi.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-xs text-muted-foreground">Nomor invoice</p>
                    <p class="font-mono font-semibold">{{ transaction.invoice_number || transaction.reference }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <label for="customer_name" class="text-sm font-medium">Nama pelanggan</label>
                        <input id="customer_name" v-model="form.customer_name" type="text" maxlength="255" placeholder="Nama pelanggan" class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                        <p v-if="form.errors.customer_name" class="text-sm text-destructive">{{ form.errors.customer_name }}</p>
                    </div>
                    <div class="space-y-2">
                        <label for="amount" class="text-sm font-medium">Total tagihan (Rp)</label>
                        <input id="amount" v-model="form.amount" type="number" min="0" step="1" :readonly="['membership', 'trainer_package', 'class', 'booking'].includes(item.type)" required class="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring" />
                        <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                        <p v-else-if="['membership', 'trainer_package', 'class', 'booking'].includes(item.type)" class="text-xs text-muted-foreground">Harga mengikuti item yang dipilih dan akan divalidasi ulang saat disimpan.</p>
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
                        <p class="text-xs text-muted-foreground">Total dikurangi nominal yang dibayarkan.</p>
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
    </div>
</template>
