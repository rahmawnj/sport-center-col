<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    EllipsisVertical,
    Package as PackageIcon,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
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
    trainer_share_percentage: number;
    description: string | null;
    is_active: boolean;
    facility_id: number | null;
    facility: { id: number; name: string } | null;
};
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
    facilities: { id: number; name: string }[];
    stats: { total: number; active: number; inactive: number };
}>();

const statCards = computed(() => [
    { label: 'Total master paket', value: props.stats.total, icon: PackageIcon, accent: 'blue' as const },
    { label: 'Paket aktif', value: props.stats.active, icon: PackageIcon, accent: 'emerald' as const },
    { label: 'Paket nonaktif', value: props.stats.inactive, icon: PackageIcon, accent: 'amber' as const },
]);

const showPackageForm = ref(false);
const editing = ref<TrainerPackage | null>(null);
const packageForm = useForm({
    name: '',
    facility_id: '',
    total_sessions: 5,
    duration_days: 30,
    price: '',
    trainer_share_percentage: 0,
    description: '',
    is_active: true,
});
const packageTitle = computed(() => editing.value ? 'Edit paket trainer' : 'Tambah paket trainer');

function openCreatePackage() {
    editing.value = null;
    packageForm.reset();
    packageForm.clearErrors();
    packageForm.name = '';
    packageForm.facility_id = '';
    packageForm.total_sessions = 5;
    packageForm.duration_days = 30;
    packageForm.price = '';
    packageForm.trainer_share_percentage = 0;
    packageForm.description = '';
    packageForm.is_active = true;
    showPackageForm.value = true;
}
function editPackage(item: TrainerPackage) {
    editing.value = item;
    packageForm.clearErrors();
    packageForm.name = item.name;
    packageForm.facility_id = item.facility_id ? String(item.facility_id) : '';
    packageForm.total_sessions = item.total_sessions;
    packageForm.duration_days = item.duration_days;
    packageForm.price = String(item.price);
    packageForm.trainer_share_percentage = Number(item.trainer_share_percentage ?? 0);
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

function goToPage(url: string | null) {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
}
function rupiah(value: string | number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}
</script>

<template>
    <Head title="Paket Personal Trainer" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Master Data"
            title="Master Paket Trainer"
            description="Kelola paket personal trainer yang tersedia untuk dijual."
        />
        <SummaryCards :items="statCards" />

        <div class="flex justify-end">
            <Button @click="openCreatePackage">
                <Plus class="mr-2 size-4" /> Tambah paket
            </Button>
        </div>

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
                                <th class="px-4 py-3 font-medium">Olahraga</th>
                                <th class="px-4 py-3 font-medium">Jumlah sesi</th>
                                <th class="px-4 py-3 font-medium">Masa berlaku</th>
                                <th class="px-4 py-3 font-medium">Harga</th>
                                <th class="px-4 py-3 font-medium">Bagian trainer</th>
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
                                <td class="px-4 py-3">{{ item.facility?.name || 'Belum dipilih' }}</td>
                                <td class="px-4 py-3">{{ item.total_sessions }} sesi</td>
                                <td class="px-4 py-3">{{ item.duration_days }} hari</td>
                                <td class="px-4 py-3 font-medium">{{ rupiah(item.price) }}</td>
                                <td class="px-4 py-3 font-medium">{{ Number(item.trainer_share_percentage).toLocaleString("id-ID") }}%</td>
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
                                <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">Belum ada master paket trainer.</td>
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
                    <div class="grid gap-2 sm:col-span-2"><Label>Olahraga paket</Label><select v-model="packageForm.facility_id" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="">Pilih olahraga</option><option v-for="facility in facilities" :key="facility.id" :value="String(facility.id)">{{ facility.name }}</option></select><p v-if="packageForm.errors.facility_id" class="text-xs text-destructive">{{ packageForm.errors.facility_id }}</p></div>
                    <div class="grid gap-2"><Label>Jumlah sesi</Label><Input v-model.number="packageForm.total_sessions" type="number" min="1" /><p v-if="packageForm.errors.total_sessions" class="text-xs text-destructive">{{ packageForm.errors.total_sessions }}</p></div>
                    <div class="grid gap-2"><Label>Masa berlaku (hari)</Label><Input v-model.number="packageForm.duration_days" type="number" min="1" /><p v-if="packageForm.errors.duration_days" class="text-xs text-destructive">{{ packageForm.errors.duration_days }}</p></div>
                    <div class="grid gap-2"><Label>Harga (Rp)</Label><Input v-model="packageForm.price" type="number" min="0" placeholder="900000" /><p v-if="packageForm.errors.price" class="text-xs text-destructive">{{ packageForm.errors.price }}</p></div>
                    <div class="grid gap-2"><Label>Bagian trainer (%)</Label><Input v-model.number="packageForm.trainer_share_percentage" type="number" min="0" max="100" step="0.01" placeholder="40" /><p v-if="packageForm.errors.trainer_share_percentage" class="text-xs text-destructive">{{ packageForm.errors.trainer_share_percentage }}</p><p class="text-xs text-muted-foreground">Persentase dari harga paket yang menjadi hak trainer.</p></div>
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

</template>
