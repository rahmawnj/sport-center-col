<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { BadgeCheck, CalendarDays, EllipsisVertical, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type MembershipPackage = { id: number; name: string; facility_id: number; facility_name: string | null; duration_days: number; price: number; member_count: number };
type LinkItem = { url: string | null; label: string; active: boolean };
const props = defineProps<{
    packages: { data: MembershipPackage[]; current_page: number; last_page: number; total: number; links: LinkItem[] };
    facilities: { id: number; name: string }[];
    stats: { packages: number; members: number };
}>();

const showForm = ref(false);
const editing = ref<MembershipPackage | null>(null);
const form = useForm({ name: '', facility_id: '', duration_days: 30, price: '' });
const title = computed(() => editing.value ? 'Edit paket membership' : 'Tambah paket membership');

function resetForm() {
    form.reset();
    form.clearErrors();
    form.name = '';
    form.facility_id = '';
    form.duration_days = 30;
    form.price = '';
}
function createPackage() {
    editing.value = null;
    resetForm();
    showForm.value = true;
}
function editPackage(item: MembershipPackage) {
    editing.value = item;
    form.clearErrors();
    form.name = item.name;
    form.facility_id = String(item.facility_id);
    form.duration_days = item.duration_days;
    form.price = String(item.price);
    showForm.value = true;
}
function closeForm() {
    showForm.value = false;
    editing.value = null;
    form.clearErrors();
}
function savePackage() {
    const options = { preserveScroll: true, onSuccess: closeForm };
    if (editing.value) form.put('/membership-packages/' + editing.value.id, options);
    else form.post('/membership-packages', options);
}
function deletePackage(item: MembershipPackage) {
    if (window.confirm('Hapus paket membership ' + item.name + '?')) {
        router.delete('/membership-packages/' + item.id, { preserveScroll: true });
    }
}
function goToPage(url: string | null) {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
}
function rupiah(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
}
</script>

<template>
    <Head title="Paket Membership" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader eyebrow="Member" title="Paket Membership" description="Kelola paket membership dengan satu harga tetap untuk setiap olahraga." />
        <SummaryCards :items="[
            { label: 'Master paket', value: props.stats.packages, icon: CalendarDays, accent: 'primary' },
            { label: 'Member terdaftar', value: props.stats.members, icon: BadgeCheck, accent: 'emerald' },
        ]" />
        <div class="flex justify-end">
            <Button @click="createPackage"><Plus class="mr-2 size-4" />Tambah paket</Button>
        </div>
        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama paket</th>
                            <th class="px-4 py-3 font-medium">Olahraga</th>
                            <th class="px-4 py-3 font-medium">Masa aktif</th>
                            <th class="px-4 py-3 font-medium">Harga tetap</th>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in props.packages.data" :key="item.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.facility_name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.duration_days }} hari</td>
                            <td class="px-4 py-3 font-semibold">{{ rupiah(item.price) }}</td>
                            <td class="px-4 py-3">{{ item.member_count }} member</td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger :as-child="true"><Button variant="ghost" size="icon" title="Aksi"><EllipsisVertical class="size-4" /></Button></DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-36">
                                        <DropdownMenuItem @click="editPackage(item)"><Pencil class="size-4" />Edit</DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem variant="destructive" @click="deletePackage(item)"><Trash2 class="size-4" />Hapus</DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="!props.packages.data.length">
                            <td colspan="6" class="px-4 py-14 text-center text-muted-foreground">
                                <BadgeCheck class="mx-auto size-10 opacity-40" />
                                <p class="mt-3 font-medium">Belum ada paket membership</p>
                                <p class="mt-1 text-sm">Tambahkan paket untuk olahraga yang membuka membership.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="props.packages.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-muted-foreground">Halaman {{ props.packages.current_page }} dari {{ props.packages.last_page }} · {{ props.packages.total }} paket</p>
                <div class="flex flex-wrap gap-1">
                    <button v-for="link in props.packages.links" :key="link.label" :disabled="!link.url" class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" @click="goToPage(link.url)" v-html="link.label" />
                </div>
            </div>
        </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeForm">
        <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl border bg-background shadow-2xl">
            <div class="sticky top-0 flex items-start justify-between border-b bg-background p-5">
                <div><p class="text-sm font-medium text-primary">Master data membership</p><h2 class="mt-1 text-xl font-semibold">{{ title }}</h2></div>
                <Button variant="ghost" size="icon" @click="closeForm"><X class="size-4" /></Button>
            </div>
            <form class="space-y-5 p-5" @submit.prevent="savePackage">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label>Nama paket</Label><Input v-model="form.name" placeholder="Membership Bulanan" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Olahraga</Label>
                        <select v-model="form.facility_id" required class="h-10 rounded-md border bg-background px-3 text-sm">
                            <option value="">Pilih olahraga</option>
                            <option v-for="facility in props.facilities" :key="facility.id" :value="String(facility.id)">{{ facility.name }}</option>
                        </select>
                        <p v-if="form.errors.facility_id" class="text-xs text-destructive">{{ form.errors.facility_id }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Masa aktif (hari)</Label><Input v-model.number="form.duration_days" type="number" min="1" />
                        <p v-if="form.errors.duration_days" class="text-xs text-destructive">{{ form.errors.duration_days }}</p>
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label>Harga tetap (Rp)</Label><Input v-model="form.price" type="number" min="0" placeholder="150000" />
                        <p class="text-xs text-muted-foreground">Satu harga untuk seluruh hari dan jam. Tidak menggunakan pricing rule.</p>
                        <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeForm">Batal</Button>
                    <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan paket' }}</Button>
                </div>
            </form>
        </div>
    </div>
</template>
