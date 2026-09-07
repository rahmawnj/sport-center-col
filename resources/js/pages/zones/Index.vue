<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CheckCircle2, CircleDollarSign, Pencil, Plus, Search, Trash2, Users, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Zone = { id: number; name: string; pricing_model: string; is_online_bookable: boolean; zone_spaces_count: number; created_at: string | null };
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = { zones: { data: Zone[]; current_page: number; last_page: number; total: number; links: LinkItem[] }; filters: { search: string; online: string }; stats: { total: number; online: number; offline: number } };
const props = defineProps<Props>();
const showForm = ref(false); const editing = ref<Zone | null>(null); const formKey = ref(0);
const search = ref(props.filters.search ?? ''); const online = ref(props.filters.online ?? 'all');
const title = computed(() => editing.value ? 'Edit zone' : 'Add new zone');
const action = computed(() => editing.value ? `/zones/${editing.value.id}` : '/zones');
const method = computed(() => editing.value ? 'put' : 'post');
const pricingLabel: Record<string, string> = { per_person: 'Per Person', per_space: 'Per Space', per_trainer_session: 'Per Trainer Session', per_table: 'Per Table' };
function openCreate() { editing.value = null; formKey.value++; showForm.value = true; }
function openEdit(zone: Zone) { editing.value = zone; formKey.value++; showForm.value = true; }
function closeForm() { showForm.value = false; editing.value = null; }
function filter() { router.get('/zones', { search: search.value || undefined, online: online.value === 'all' ? undefined : online.value }, { preserveState: true, replace: true }); }
function reset() { search.value = ''; online.value = 'all'; filter(); }
function removeZone(zone: Zone) { if (!window.confirm(`Hapus zone ${zone.name}?`)) return; router.delete(`/zones/${zone.id}`, { preserveScroll: true }); }
function formatDate(value: string | null) { if (!value) return '—'; return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)); }
</script>

<template>
<Head title="Zones" />
<div class="space-y-6 p-4 md:p-6">
  <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="text-sm font-medium text-primary">Master Data</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Zones</h1><p class="mt-1 text-sm text-muted-foreground">Kelola area olahraga dan aturan harga dasar Sport Center.</p></div>
    <Button @click="openCreate"><Plus class="mr-2 size-4" /> Add zone</Button>
  </div>
  <div class="grid gap-3 sm:grid-cols-3">
    <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Total zones</span><Users class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.total }}</div></div>
    <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Online bookable</span><CheckCircle2 class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.online }}</div></div>
    <div class="rounded-2xl border bg-card p-4 shadow-sm"><div class="flex justify-between"><span class="text-sm text-muted-foreground">Offline booking</span><CircleDollarSign class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ props.stats.offline }}</div></div>
  </div>
  <div class="rounded-2xl border bg-card shadow-sm">
    <div class="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
      <div class="relative flex-1"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" @keyup.enter="filter" class="pl-9" placeholder="Search zone..." /></div>
      <select v-model="online" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="all">All booking status</option><option value="online">Online bookable</option><option value="offline">Offline</option></select>
      <Button variant="secondary" @click="filter">Filter</Button><Button variant="ghost" @click="reset">Reset</Button>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-5 py-3 font-medium">Zone</th><th class="px-5 py-3 font-medium">Pricing model</th><th class="px-5 py-3 font-medium">Booking</th><th class="px-5 py-3 font-medium">Spaces</th><th class="px-5 py-3 font-medium">Created</th><th class="px-5 py-3 text-right font-medium">Action</th></tr></thead>
    <tbody class="divide-y"><tr v-for="zone in props.zones.data" :key="zone.id" class="hover:bg-muted/20"><td class="px-5 py-4"><div class="font-medium">{{ zone.name }}</div><div class="text-xs text-muted-foreground">Zone #{{ zone.id }}</div></td><td class="px-5 py-4"><span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ pricingLabel[zone.pricing_model] ?? zone.pricing_model }}</span></td><td class="px-5 py-4"><span :class="zone.is_online_bookable ? 'text-emerald-600' : 'text-muted-foreground'" class="text-xs font-medium">{{ zone.is_online_bookable ? 'Online bookable' : 'Not bookable online' }}</span></td><td class="px-5 py-4 text-muted-foreground">{{ zone.zone_spaces_count }} spaces</td><td class="px-5 py-4 text-muted-foreground">{{ formatDate(zone.created_at) }}</td><td class="px-5 py-4"><div class="flex justify-end gap-1"><Button variant="ghost" size="icon" title="Edit" @click="openEdit(zone)"><Pencil class="size-4" /></Button><Button variant="ghost" size="icon" title="Delete" :disabled="zone.zone_spaces_count > 0" @click="removeZone(zone)"><Trash2 class="size-4 text-destructive" /></Button></div></td></tr>
    <tr v-if="props.zones.data.length === 0"><td colspan="6" class="px-5 py-16 text-center"><Users class="mx-auto size-10 text-muted-foreground/50" /><p class="mt-3 font-medium">No zones found</p><p class="mt-1 text-sm text-muted-foreground">Coba ubah filter atau tambahkan zone baru.</p></td></tr></tbody></table></div>
    <div v-if="props.zones.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-muted-foreground">Page {{ props.zones.current_page }} of {{ props.zones.last_page }} · {{ props.zones.total }} zones</p><div class="flex flex-wrap gap-1"><a v-for="link in props.zones.links" :key="link.label" :href="link.url ?? '#'" @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })"><Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" v-html="link.label" /></a></div></div>
  </div>
</div>
<div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeForm"><div class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl"><div class="flex items-start justify-between border-b p-5"><div><p class="text-sm font-medium text-primary">Zone management</p><h2 class="mt-1 text-xl font-semibold">{{ title }}</h2><p class="mt-1 text-sm text-muted-foreground">Sesuai struktur tabel <code>zones</code>.</p></div><Button variant="ghost" size="icon" @click="closeForm"><X class="size-4" /></Button></div>
<Form :key="formKey" :action="action" :method="method" class="space-y-5 p-5" @success="closeForm" v-slot="{ errors, processing }"><div class="grid gap-4"><div class="grid gap-2"><Label for="zone-name">Zone name</Label><Input id="zone-name" name="name" :default-value="editing?.name" required placeholder="e.g. Gym" /><p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p></div><div class="grid gap-2"><Label for="pricing-model">Pricing model</Label><select id="pricing-model" name="pricing_model" :value="editing?.pricing_model ?? ''" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="" disabled>Select pricing model</option><option value="per_person">Per Person</option><option value="per_space">Per Space</option><option value="per_trainer_session">Per Trainer Session</option><option value="per_table">Per Table</option></select><p v-if="errors.pricing_model" class="text-xs text-destructive">{{ errors.pricing_model }}</p></div><div class="grid gap-2"><Label for="online-bookable">Online booking</Label><select id="online-bookable" name="is_online_bookable" :value="editing?.is_online_bookable ? '1' : '0'" class="h-10 rounded-md border bg-background px-3 text-sm"><option value="1">Yes — bookable online</option><option value="0">No — offline only</option></select><p v-if="errors.is_online_bookable" class="text-xs text-destructive">{{ errors.is_online_bookable }}</p></div></div><div class="flex justify-end gap-2 border-t pt-4"><Button type="button" variant="ghost" @click="closeForm">Cancel</Button><Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : editing ? 'Save changes' : 'Create zone' }}</Button></div></Form></div></div>
</template>
