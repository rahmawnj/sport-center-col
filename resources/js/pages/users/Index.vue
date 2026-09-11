<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowRight, CheckCircle2, Eye, Mail, Pencil, Plus, Search, ShieldCheck, Trash2, Users as UsersIcon, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Role = { id: number; name: string };
type User = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    email_verified_at: string | null;
    created_at: string | null;
    deleted_at: string | null;
    role: Role | null;
    transactions_count: number;
    subscriptions_count: number;
};
type PaginationLink = { url: string | null; label: string; active: boolean };
type PaginatedUsers = { data: User[]; current_page: number; last_page: number; total: number; links: PaginationLink[] };
type Props = { users: PaginatedUsers; roles: Role[]; filters: { search: string; role_id: number | string | null; status: string }; stats: { total: number; admins: number; members: number; verified: number } };

const props = defineProps<Props>();
const showForm = ref(false);
const editing = ref<User | null>(null);
const formKey = ref(0);
const search = ref(props.filters.search ?? '');
const roleId = ref(String(props.filters.role_id ?? ''));
const status = ref(props.filters.status ?? 'all');
const title = computed(() => editing.value ? 'Edit pengguna' : 'Tambah pengguna baru');
const formAction = computed(() => editing.value ? `/users/${editing.value.id}` : '/users');
const formMethod = computed(() => editing.value ? 'put' : 'post');

function openCreate() { editing.value = null; formKey.value++; showForm.value = true; }
function openEdit(user: User) { editing.value = user; formKey.value++; showForm.value = true; }
function closeForm() { showForm.value = false; editing.value = null; }
function applyFilters() { router.get('/users', { search: search.value || undefined, role_id: roleId.value || undefined, status: status.value === 'all' ? undefined : status.value }, { preserveState: true, replace: true }); }
function resetFilters() { search.value = ''; roleId.value = ''; status.value = 'all'; applyFilters(); }
function removeUser(user: User) { if (!window.confirm(`Nonaktifkan akun ${user.name}?`)) return; router.delete(`/users/${user.id}`, { preserveScroll: true }); }
function initials(name: string) { return name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase(); }
function formatDate(value: string | null) { if (!value) return '—'; return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)); }
</script>

<template>
    <Head title="Pengguna" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-medium text-primary">Manajemen</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">Pengguna</h1><p class="mt-1 text-sm text-muted-foreground">Kelola akun, peran, akses, dan aktivitas pengguna Sport Center.</p></div>
            <Button @click="openCreate"><Plus class="mr-2 size-4" /> Tambah pengguna</Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="item in [{ label: 'Total pengguna', value: props.stats.total, icon: UsersIcon }, { label: 'Administrator', value: props.stats.admins, icon: ShieldCheck }, { label: 'Anggota', value: props.stats.members, icon: UsersIcon }, { label: 'Email terverifikasi', value: props.stats.verified, icon: CheckCircle2 }]" :key="item.label" class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between"><span class="text-sm text-muted-foreground">{{ item.label }}</span><component :is="item.icon" class="size-4 text-muted-foreground" /></div><div class="mt-3 text-2xl font-semibold">{{ item.value }}</div>
            </div>
        </div>

        <div class="rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center">
                <div class="relative flex-1"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" @keyup.enter="applyFilters" class="pl-9" placeholder="Cari nama, email, atau nomor telepon..." /></div>
                <select v-model="roleId" class="h-10 rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring"><option value="">Semua peran</option><option v-for="role in props.roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option></select>
                <select v-model="status" class="h-10 rounded-md border bg-background px-3 text-sm outline-none focus:ring-2 focus:ring-ring"><option value="all">Semua status</option><option value="active">Aktif</option><option value="deleted">Dinonaktifkan</option></select>
                <Button variant="secondary" @click="applyFilters">Filter</Button><Button variant="ghost" @click="resetFilters">Reset</Button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm"><thead class="bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-5 py-3 font-medium">Pengguna</th><th class="px-5 py-3 font-medium">Peran</th><th class="px-5 py-3 font-medium">Aktivitas</th><th class="px-5 py-3 font-medium">Bergabung</th><th class="px-5 py-3 text-right font-medium">Aksi</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="user in props.users.data" :key="user.id" class="transition-colors hover:bg-muted/20">
                        <td class="px-5 py-4"><div class="flex min-w-[240px] items-center gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">{{ initials(user.name) }}</div><div class="min-w-0"><Link :href="`/users/${user.id}`" class="block truncate font-medium hover:underline">{{ user.name }}</Link><div class="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground"><Mail class="size-3" />{{ user.email }}</div></div></div></td>
                        <td class="px-5 py-4"><span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ user.role?.name ?? 'Tanpa peran' }}</span><span v-if="user.email_verified_at" class="ml-2 text-xs text-emerald-600">Terverifikasi</span></td>
                        <td class="px-5 py-4 text-muted-foreground"><div>{{ user.transactions_count }} transaksi</div><div class="text-xs">{{ user.subscriptions_count }} langganan</div></td><td class="px-5 py-4 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                        <td class="px-5 py-4"><div class="flex justify-end gap-1"><Button variant="ghost" size="icon" as-child title="Lihat detail"><Link :href="`/users/${user.id}`"><Eye class="size-4" /></Link></Button><Button variant="ghost" size="icon" title="Edit" @click="openEdit(user)"><Pencil class="size-4" /></Button><Button v-if="!user.deleted_at" variant="ghost" size="icon" title="Nonaktifkan" @click="removeUser(user)"><Trash2 class="size-4 text-destructive" /></Button></div></td>
                    </tr>
                    <tr v-if="props.users.data.length === 0"><td colspan="5" class="px-5 py-16 text-center"><UsersIcon class="mx-auto size-10 text-muted-foreground/50" /><p class="mt-3 font-medium">Pengguna tidak ditemukan</p><p class="mt-1 text-sm text-muted-foreground">Coba ubah filter atau tambahkan pengguna baru.</p></td></tr>
                </tbody></table>
            </div>
            <div v-if="props.users.last_page > 1" class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-muted-foreground">Halaman {{ props.users.current_page }} dari {{ props.users.last_page }} · {{ props.users.total }} pengguna</p><div class="flex flex-wrap gap-1"><Link v-for="link in props.users.links" :key="link.label" :href="link.url ?? '#'" preserve-state><Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url" v-html="link.label" /></Link></div></div>
        </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeForm">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5"><div><p class="text-sm font-medium text-primary">Manajemen pengguna</p><h2 class="mt-1 text-xl font-semibold">{{ title }}</h2><p class="mt-1 text-sm text-muted-foreground">Atur identitas dan akses pengguna.</p></div><Button variant="ghost" size="icon" @click="closeForm"><X class="size-4" /></Button></div>
            <Form :key="formKey" :action="formAction" :method="formMethod" class="space-y-5 p-5" @success="closeForm" v-slot="{ errors, processing }">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2 sm:col-span-2"><Label for="user-name">Nama lengkap</Label><Input id="user-name" name="name" :default-value="editing?.name" required placeholder="contoh: Rahma Wijaya" /><p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p></div>
                    <div class="grid gap-2"><Label for="user-email">Email</Label><Input id="user-email" type="email" name="email" :default-value="editing?.email" required placeholder="nama@contoh.com" /><p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p></div>
                    <div class="grid gap-2"><Label for="user-phone">Nomor telepon</Label><Input id="user-phone" name="phone" :default-value="editing?.phone ?? undefined" placeholder="08xxxxxxxxxx" /><p v-if="errors.phone" class="text-xs text-destructive">{{ errors.phone }}</p></div>
                    <div class="grid gap-2 sm:col-span-2"><Label for="user-role">Peran</Label><select id="user-role" name="role_id" :value="editing?.role?.id ?? ''" required class="h-10 rounded-md border bg-background px-3 text-sm"><option value="" disabled>Pilih peran</option><option v-for="role in props.roles" :key="role.id" :value="role.id">{{ role.name }}</option></select><p v-if="errors.role_id" class="text-xs text-destructive">{{ errors.role_id }}</p></div>
                    <div class="grid gap-2"><Label for="user-password">Kata sandi {{ editing ? '(opsional)' : '' }}</Label><Input id="user-password" type="password" name="password" :required="!editing" minlength="8" placeholder="Minimal 8 karakter" /><p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p></div>
                    <div class="grid gap-2"><Label for="user-password-confirmation">Konfirmasi kata sandi</Label><Input id="user-password-confirmation" type="password" name="password_confirmation" :required="!editing" minlength="8" placeholder="Ulangi kata sandi" /></div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4"><Button type="button" variant="ghost" @click="closeForm">Batal</Button><Button type="submit" :disabled="processing">{{ processing ? 'Menyimpan...' : editing ? 'Simpan perubahan' : 'Buat pengguna' }}<ArrowRight class="ml-2 size-4" /></Button></div>
            </Form>
        </div>
    </div>
</template>
