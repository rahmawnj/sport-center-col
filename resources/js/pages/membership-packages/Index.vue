<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { BadgeCheck, CalendarDays, EllipsisVertical, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Rule = { id?: number; day_type: 'all' | 'weekday' | 'weekend'; date_start: string | null; date_end: string | null; start_time: string | null; end_time: string | null; price: number; priority: number };
type MembershipPackage = { id: number; name: string; facility_id: number; facility_name: string | null; duration_days: number; pricing_rules: Rule[]; member_count: number };
type LinkItem = { url: string | null; label: string; active: boolean };
const props = defineProps<{
    packages: { data: MembershipPackage[]; current_page: number; last_page: number; total: number; links: LinkItem[] };
    facilities: { id: number; name: string }[];
    stats: { packages: number; members: number };
}>();

const showForm = ref(false);
const editing = ref<MembershipPackage | null>(null);
const ruleKey = ref(0);
const form = useForm({
    name: '',
    facility_id: '',
    duration_days: 30,
    pricing_rules: [{ day_type: 'all' as 'all' | 'weekday' | 'weekend', date_start: '', date_end: '', start_time: '', end_time: '', price: '', priority: 0 }],
});
const title = computed(() => editing.value ? 'Edit paket membership' : 'Tambah paket membership');

function resetForm() {
    form.reset();
    form.clearErrors();
    form.name = '';
    form.facility_id = '';
    form.duration_days = 30;
    form.pricing_rules = [{ day_type: 'all', date_start: '', date_end: '', start_time: '', end_time: '', price: '', priority: 0 }];
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
    form.pricing_rules = item.pricing_rules.map(rule => ({
        day_type: rule.day_type,
        date_start: rule.date_start ?? '',
        date_end: rule.date_end ?? '',
        start_time: rule.start_time ?? '',
        end_time: rule.end_time ?? '',
        price: String(rule.price),
        priority: rule.priority ?? 0,
    }));
    if (!form.pricing_rules.length) form.pricing_rules = [{ day_type: 'all', date_start: '', date_end: '', start_time: '', end_time: '', price: '', priority: 0 }];
    ruleKey.value++;
    showForm.value = true;
}
function addRule() {
    form.pricing_rules.push({ day_type: 'all', date_start: '', date_end: '', start_time: '', end_time: '', price: '', priority: 0 });
}
function removeRule(index: number) {
    if (form.pricing_rules.length > 1) form.pricing_rules.splice(index, 1);
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
function dayLabel(value: Rule['day_type']) {
    return value === 'all' ? 'Semua hari' : value === 'weekday' ? 'Weekday' : 'Weekend';
}
</script>

<template>
    <Head title="Paket Membership" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader eyebrow="Member" title="Paket Membership" description="Kelola master paket membership yang tersedia untuk setiap olahraga." />
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
                            <th class="px-4 py-3 font-medium">Aturan harga</th>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in props.packages.data" :key="item.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                            <td class="px-4 py-3">{{ item.facility_name || '—' }}</td>
                            <td class="px-4 py-3">{{ item.duration_days }} hari</td>
                            <td class="px-4 py-3">
                                <div v-for="rule in item.pricing_rules" :key="rule.id" class="flex flex-wrap justify-between gap-x-3 gap-y-1">
                                    <span class="text-muted-foreground">{{ dayLabel(rule.day_type) }}<template v-if="rule.start_time && rule.end_time"> · {{ rule.start_time }}–{{ rule.end_time }}</template></span>
                                    <span class="font-medium">{{ rupiah(rule.price) }}</span>
                                </div>
                                <span v-if="!item.pricing_rules.length" class="text-muted-foreground">Belum ada harga</span>
                            </td>
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
                </div>
                <div class="space-y-3 border-t pt-4">
                    <div class="flex items-center justify-between gap-2">
                        <div><h3 class="font-semibold">Aturan harga</h3><p class="text-xs text-muted-foreground">Atur harga berdasarkan hari dan jam. Bisa menambahkan harga promo.</p></div>
                        <Button type="button" size="sm" variant="outline" @click="addRule"><Plus class="mr-1 size-4" />Tambah aturan</Button>
                    </div>
                    <div v-for="(rule, index) in form.pricing_rules" :key="index" class="space-y-3 rounded-xl border p-3">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="grid gap-1"><Label class="text-xs">Hari</Label><select v-model="rule.day_type" class="h-9 rounded-md border bg-background px-2 text-sm"><option value="all">Semua hari</option><option value="weekday">Weekday</option><option value="weekend">Weekend</option></select></div>
                            <div class="grid gap-1"><Label class="text-xs">Mulai jam</Label><Input v-model="rule.start_time" type="time" /></div>
                            <div class="grid gap-1"><Label class="text-xs">Sampai jam</Label><Input v-model="rule.end_time" type="time" /></div>
                            <div class="grid gap-1"><Label class="text-xs">Harga (Rp)</Label><Input v-model="rule.price" type="number" min="0" /></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="grid gap-1"><Label class="text-xs">Promo mulai</Label><Input v-model="rule.date_start" type="date" /></div>
                            <div class="grid gap-1"><Label class="text-xs">Promo sampai</Label><Input v-model="rule.date_end" type="date" /></div>
                            <div class="flex items-end justify-between gap-2"><div class="grid flex-1 gap-1"><Label class="text-xs">Prioritas</Label><Input v-model.number="rule.priority" type="number" /></div><Button type="button" variant="ghost" size="icon" class="text-destructive" :disabled="form.pricing_rules.length === 1" @click="removeRule(index)"><Trash2 class="size-4" /></Button></div>
                        </div>
                        <p v-if="form.errors['pricing_rules.' + index + '.price']" class="text-xs text-destructive">{{ form.errors['pricing_rules.' + index + '.price'] }}</p>
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
