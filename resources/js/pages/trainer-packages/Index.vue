<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    Dumbbell,
    EllipsisVertical,
    Package as PackageIcon,
    Pencil,
    Plus,
    RefreshCw,
    ShoppingCart,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TrainerPackage = {
    id: number;
    name: string;
    total_sessions: number;
    duration_days: number;
    price: number;
    description: string | null;
    is_active: boolean;
    trainers: { id: number; name: string }[];
    trainer_ids: number[];
};
type MemberPackage = {
    id: number;
    member_name: string | null;
    trainer_name: string | null;
    facility_name: string | null;
    package_name: string | null;
    start_date: string;
    expires_at: string;
    total_sessions: number;
    used_sessions: number;
    remaining_sessions: number;
    status: 'active' | 'pending' | 'completed' | 'expired' | 'cancelled';
};
type Option = { id: number; name: string; specialty?: string | null; facility_ids?: number[] };
type LinkItem = { url: string | null; label: string; active: boolean };
type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: LinkItem[];
};
const props = defineProps<{
    packages: Paginated<TrainerPackage>;
    packageOptions: { id: number; name: string; total_sessions: number; price: number; trainer_ids: number[] }[];
    members: Option[];
    trainers: Option[];
    facilities: { id: number; name: string }[];
    memberPackages: Paginated<MemberPackage>;
    filters: { search: string; status: string };
    stats: { packages: number; active: number; expiring: number; expired: number };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'pending', label: 'Menunggu pembayaran' },
    { value: 'completed', label: 'Sesi habis' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

const statCards = computed(() => [
    { label: 'Paket member aktif', value: props.stats.active, icon: BadgeCheck, accent: 'emerald' as const },
    { label: 'Segera expired', value: props.stats.expiring, icon: CalendarClock, accent: 'amber' as const },
    { label: 'Expired', value: props.stats.expired, icon: RefreshCw, accent: 'rose' as const },
]);

const showPackageForm = ref(false);
const editing = ref<TrainerPackage | null>(null);
const packageForm = useForm({
    name: '',
    trainer_ids: [] as number[],
    total_sessions: 5,
    duration_days: 30,
    price: '',
    description: '',
    is_active: true,
});
const packageTitle = computed(() => editing.value ? 'Edit paket trainer' : 'Tambah paket trainer');

function openCreatePackage() {
    editing.value = null;
    packageForm.reset();
    packageForm.clearErrors();
    packageForm.name = '';
    packageForm.trainer_ids = [];
    packageForm.total_sessions = 5;
    packageForm.duration_days = 30;
    packageForm.price = '';
    packageForm.description = '';
    packageForm.is_active = true;
    showPackageForm.value = true;
}
function editPackage(item: TrainerPackage) {
    editing.value = item;
    packageForm.clearErrors();
    packageForm.name = item.name;
    packageForm.trainer_ids = [...item.trainer_ids];
    packageForm.total_sessions = item.total_sessions;
    packageForm.duration_days = item.duration_days;
    packageForm.price = String(item.price);
    packageForm.description = item.description ?? '';
    packageForm.is_active = item.is_active;
    showPackageForm.value = true;
}
function closePackageForm() {
    showPackageForm.value = false;
    editing.value = null;
    packageForm.clearErrors();
}
function savePackage() {
    const options = { preserveScroll: true, onSuccess: closePackageForm };
    if (editing.value) {
        packageForm.put('/trainer-packages/' + editing.value.id, options);
    } else {
        packageForm.post('/trainer-packages', options);
    }
}
function removePackage(item: TrainerPackage) {
    if (window.confirm('Hapus paket ' + item.name + '?')) {
        router.delete('/trainer-packages/' + item.id, { preserveScroll: true });
    }
}

const showPurchaseForm = ref(false);
const purchaseForm = useForm({
    user_id: '',
    facility_id: '',
    trainer_id: '',
    trainer_package_id: '',
    start_date: new Date().toLocaleDateString('en-CA'),
    payment_method: 'cash',
    payment_status: 'paid',
});
const selectedPackage = computed(() => props.packageOptions.find((item) => item.id === Number(purchaseForm.trainer_package_id)));
const availableTrainers = computed(() => props.trainers.filter((trainer) => trainer.facility_ids?.includes(Number(purchaseForm.facility_id)) && selectedPackage.value?.trainer_ids.includes(trainer.id)));

function openPurchase() {
    purchaseForm.reset();
    purchaseForm.clearErrors();
    purchaseForm.start_date = new Date().toLocaleDateString('en-CA');
    purchaseForm.facility_id = '';
    purchaseForm.trainer_id = '';
    showPurchaseForm.value = true;
}
function closePurchase() {
    showPurchaseForm.value = false;
    purchaseForm.clearErrors();
}
function purchase() {
    purchaseForm.post('/trainer-packages/purchase', {
        preserveScroll: true,
        onSuccess: closePurchase,
    });
}

function filter() {
    router.get('/trainer-packages', {
        search: search.value || undefined,
        status: status.value === 'all' ? undefined : status.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}
function resetFilters() {
    search.value = '';
    status.value = 'all';
    filter();
}
function goToPage(url: string | null) {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
}
function rupiah(value: string | number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}
function date(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value + 'T00:00:00'));
}
const statusMeta: Record<MemberPackage['status'], { label: string; badge: string; dot: string }> = {
    active: { label: 'Aktif', badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    pending: { label: 'Menunggu pembayaran', badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400', dot: 'bg-amber-500' },
    completed: { label: 'Sesi habis', badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400', dot: 'bg-sky-500' },
    expired: { label: 'Expired', badge: 'bg-rose-500/10 text-rose-600 dark:text-rose-400', dot: 'bg-rose-500' },
    cancelled: { label: 'Dibatalkan', badge: 'bg-muted text-muted-foreground', dot: 'bg-muted-foreground/60' },
};
function useSession(item: MemberPackage) {
    if (!window.confirm('Gunakan 1 sesi untuk ' + (item.member_name ?? 'member ini') + '?')) return;
    router.post('/trainer-packages/' + item.id + '/use-session', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Paket Personal Trainer" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Member"
            title="Paket Personal Trainer"
            description="Pantau paket latihan member, sisa sesi, dan masa berlakunya."
        />
        <SummaryCards :items="statCards" />

        <div class="flex flex-wrap justify-end gap-2">
            <Button variant="outline" @click="openCreatePackage">
                <Plus class="mr-2 size-4" /> Tambah paket
            </Button>
            <Button @click="openPurchase" :disabled="!members.length || !trainers.length || !packageOptions.length">
                <ShoppingCart class="mr-2 size-4" /> Catat pembelian
            </Button>
        </div>

        <section class="space-y-4">
            <div class="flex items-center gap-2">
                <Dumbbell class="size-4 text-primary" />
                <h2 class="font-semibold">Paket yang dimiliki member</h2>
            </div>
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari member, paket, atau trainer..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="resetFilters"
                />
            </div>
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Member</th>
                                <th class="px-4 py-3 font-medium">Paket / Trainer</th>
                                <th class="px-4 py-3 font-medium">Sesi tersisa</th>
                                <th class="px-4 py-3 font-medium">Periode</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in memberPackages.data" :key="item.id" class="hover:bg-muted/30">
                                <td class="px-4 py-3 font-medium">{{ item.member_name || '—' }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ item.package_name || '—' }}</div>
                                    <div class="text-xs text-muted-foreground">{{ item.facility_name || 'Olahraga belum dicatat' }} · {{ item.trainer_name || 'Belum ditentukan' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold tabular-nums">{{ item.remaining_sessions }} / {{ item.total_sessions }}</div>
                                    <div class="text-xs text-muted-foreground">{{ item.used_sessions }} sesi digunakan</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ date(item.start_date) }} – {{ date(item.expires_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusMeta[item.status].badge">
                                        <span class="size-1.5 rounded-full" :class="statusMeta[item.status].dot" />
                                        {{ statusMeta[item.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button v-if="item.status === 'active' && item.remaining_sessions > 0" size="sm" variant="outline" @click="useSession(item)">Gunakan 1 sesi</Button>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                            <tr v-if="!memberPackages.data.length">
                                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                                    <Dumbbell class="mx-auto size-10 text-muted-foreground/50" />
                                    <p class="mt-3 font-medium">Belum ada paket trainer milik member</p>
                                    <p class="mt-1 text-sm">Catat pembelian untuk mulai mengaktifkan sesi latihan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="memberPackages.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-muted-foreground">Halaman {{ memberPackages.current_page }} dari {{ memberPackages.last_page }} · {{ memberPackages.total }} paket member</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="link in memberPackages.links" :key="link.label" :disabled="!link.url" class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" @click="goToPage(link.url)" v-html="link.label" />
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center gap-2">
                <PackageIcon class="size-4 text-primary" />
                <h2 class="font-semibold">Master paket trainer</h2>
            </div>
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama paket</th>
                                <th class="px-4 py-3 font-medium">Trainer</th>
                                <th class="px-4 py-3 font-medium">Jumlah sesi</th>
                                <th class="px-4 py-3 font-medium">Masa berlaku</th>
                                <th class="px-4 py-3 font-medium">Harga</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in packages.data" :key="item.id" class="hover:bg-muted/30">
                                <td class="px-4 py-3 font-medium">
                                    {{ item.name }}
                                    <p v-if="item.description" class="mt-0.5 text-xs font-normal text-muted-foreground">{{ item.description }}</p>
                                </td>
                                <td class="px-4 py-3"><div class="flex max-w-xs flex-wrap gap-1"><span v-for="trainer in item.trainers" :key="trainer.id" class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">{{ trainer.name }}</span><span v-if="!item.trainers.length" class="text-xs text-muted-foreground">Belum di-assign</span></div></td>
                                <td class="px-4 py-3">{{ item.total_sessions }} sesi</td>
                                <td class="px-4 py-3">{{ item.duration_days }} hari</td>
                                <td class="px-4 py-3 font-medium">{{ rupiah(item.price) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="item.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger :as-child="true">
                                                <Button variant="ghost" size="icon" title="Aksi"><EllipsisVertical class="size-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-36">
                                                <DropdownMenuItem @click="editPackage(item)"><Pencil class="size-4" /> Edit</DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem variant="destructive" @click="removePackage(item)"><Trash2 class="size-4" /> Hapus</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!packages.data.length">
                                <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">Belum ada master paket trainer.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="packages.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-muted-foreground">Halaman {{ packages.current_page }} dari {{ packages.last_page }} · {{ packages.total }} master paket</p>
                    <div class="flex flex-wrap gap-1">
                        <button v-for="link in packages.links" :key="link.label" :disabled="!link.url" class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" @click="goToPage(link.url)" v-html="link.label" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div v-if="showPackageForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closePackageForm">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5">
                <div><p class="text-sm font-medium text-primary">Master data</p><h2 class="mt-1 text-xl font-semibold">{{ packageTitle }}</h2></div>
                <Button variant="ghost" size="icon" @click="closePackageForm"><X class="size-4" /></Button>
            </div>
            <form class="space-y-4 p-5" @submit.prevent="savePackage">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2"><Label>Nama paket</Label><Input v-model="packageForm.name" placeholder="Personal Trainer 10 Sesi" /><p v-if="packageForm.errors.name" class="text-xs text-destructive">{{ packageForm.errors.name }}</p></div>
                    <div class="grid gap-2 sm:col-span-2"><Label>Assign trainer</Label><div class="max-h-44 space-y-2 overflow-y-auto rounded-md border p-3"><label v-for="trainer in trainers" :key="trainer.id" class="flex cursor-pointer items-center gap-2 text-sm"><input v-model="packageForm.trainer_ids" type="checkbox" :value="trainer.id" class="size-4 rounded" /><span>{{ trainer.name }}</span><span v-if="trainer.specialty" class="text-xs text-muted-foreground">· {{ trainer.specialty }}</span></label><p v-if="!trainers.length" class="text-xs text-muted-foreground">Belum ada trainer.</p></div><p class="text-xs text-muted-foreground">Pilih satu atau beberapa trainer yang boleh menangani paket ini.</p><p v-if="packageForm.errors.trainer_ids" class="text-xs text-destructive">{{ packageForm.errors.trainer_ids }}</p></div>
                    <div class="grid gap-2"><Label>Jumlah sesi</Label><Input v-model.number="packageForm.total_sessions" type="number" min="1" /><p v-if="packageForm.errors.total_sessions" class="text-xs text-destructive">{{ packageForm.errors.total_sessions }}</p></div>
                    <div class="grid gap-2"><Label>Masa berlaku (hari)</Label><Input v-model.number="packageForm.duration_days" type="number" min="1" /><p v-if="packageForm.errors.duration_days" class="text-xs text-destructive">{{ packageForm.errors.duration_days }}</p></div>
                    <div class="grid gap-2 sm:col-span-2"><Label>Harga (Rp)</Label><Input v-model="packageForm.price" type="number" min="0" placeholder="900000" /><p v-if="packageForm.errors.price" class="text-xs text-destructive">{{ packageForm.errors.price }}</p></div>
                    <div class="grid gap-2 sm:col-span-2"><Label>Deskripsi</Label><textarea v-model="packageForm.description" rows="3" class="w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Deskripsi paket..." /></div>
                    <label v-if="editing" class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="packageForm.is_active" type="checkbox" class="size-4" /> Paket aktif dan bisa dijual</label>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closePackageForm">Batal</Button>
                    <Button type="submit" :disabled="packageForm.processing">{{ packageForm.processing ? 'Menyimpan...' : 'Simpan paket' }}</Button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="showPurchaseForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closePurchase">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5">
                <div><p class="text-sm font-medium text-primary">Transaksi PT</p><h2 class="mt-1 text-xl font-semibold">Catat pembelian paket</h2></div>
                <Button variant="ghost" size="icon" @click="closePurchase"><X class="size-4" /></Button>
            </div>
            <form class="space-y-4 p-5" @submit.prevent="purchase">
                <div class="grid gap-2"><Label>Member</Label><select v-model="purchaseForm.user_id" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih member</option><option v-for="member in members" :key="member.id" :value="String(member.id)">{{ member.name }}</option></select><p v-if="purchaseForm.errors.user_id" class="text-xs text-destructive">{{ purchaseForm.errors.user_id }}</p></div>
                <div class="grid gap-2"><Label>Olahraga</Label><select v-model="purchaseForm.facility_id" required class="h-10 rounded-md border bg-background px-3 text-sm" @change="purchaseForm.trainer_id = ''"><option value="">Pilih olahraga</option><option v-for="facility in facilities" :key="facility.id" :value="String(facility.id)">{{ facility.name }}</option></select><p v-if="purchaseForm.errors.facility_id" class="text-xs text-destructive">{{ purchaseForm.errors.facility_id }}</p></div>
                <div class="grid gap-2"><Label>Paket</Label><select v-model="purchaseForm.trainer_package_id" required class="h-10 rounded-md border bg-background px-3 text-sm" @change="purchaseForm.trainer_id = ''"><option value="">Pilih paket</option><option v-for="item in packageOptions" :key="item.id" :value="String(item.id)">{{ item.name }} · {{ item.total_sessions }} sesi · {{ rupiah(item.price) }}</option></select><p v-if="purchaseForm.errors.trainer_package_id" class="text-xs text-destructive">{{ purchaseForm.errors.trainer_package_id }}</p></div>
                <div class="grid gap-2"><Label>Trainer</Label><select v-model="purchaseForm.trainer_id" :disabled="!purchaseForm.facility_id || !availableTrainers.length" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih trainer</option><option v-for="trainer in availableTrainers" :key="trainer.id" :value="String(trainer.id)">{{ trainer.name }}{{ trainer.specialty ? ' · ' + trainer.specialty : '' }}</option></select><p v-if="purchaseForm.errors.trainer_id" class="text-xs text-destructive">{{ purchaseForm.errors.trainer_id }}</p></div>
                <div class="grid gap-2"><Label>Tanggal mulai</Label><Input v-model="purchaseForm.start_date" type="date" required /><p v-if="purchaseForm.errors.start_date" class="text-xs text-destructive">{{ purchaseForm.errors.start_date }}</p></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2"><Label>Metode bayar</Label><select v-model="purchaseForm.payment_method" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="cash">Tunai</option><option value="bank_transfer">Transfer bank</option><option value="qris">QRIS</option></select></div>
                    <div class="grid gap-2"><Label>Status bayar</Label><select v-model="purchaseForm.payment_status" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="paid">Lunas</option><option value="pending">Belum bayar</option></select></div>
                </div>
                <p class="text-xs text-muted-foreground">Paket aktif setelah pembayaran lunas. Pembelian yang belum dibayar berstatus menunggu pembayaran.</p>
                <div class="flex justify-end gap-2 border-t pt-4"><Button type="button" variant="ghost" @click="closePurchase">Batal</Button><Button type="submit" :disabled="purchaseForm.processing">{{ purchaseForm.processing ? 'Menyimpan...' : 'Simpan pembelian' }}</Button></div>
            </form>
        </div>
    </div>
</template>
