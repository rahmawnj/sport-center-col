<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { KeyRound, Pencil, Plus, Search, ShieldCheck, Trash2, X } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Admin = { id: number; name: string; email: string; created_at: string | null };
type LinkItem = { url: string | null; label: string; active: boolean };
const props = defineProps<{
    admins: { data: Admin[]; current_page: number; last_page: number; total: number; links: LinkItem[] };
    filters: { search: string };
    stats: { total: number };
    currentUserId: number;
}>();

const search = ref(props.filters.search ?? '');
const showForm = ref(false);
const editing = ref<Admin | null>(null);
const formKey = ref(0);
const title = computed(() => editing.value ? 'Edit admin' : 'Tambah admin');
const action = computed(() => editing.value ? `/admins/${editing.value.id}` : '/admins');
const method = computed(() => editing.value ? 'put' : 'post');

function openCreate() {
    editing.value = null;
    formKey.value++;
    showForm.value = true;
}
function openEdit(admin: Admin) {
    editing.value = admin;
    formKey.value++;
    showForm.value = true;
}
function closeForm() {
    showForm.value = false;
    editing.value = null;
}
function filter() {
    router.get('/admins', { search: search.value || undefined }, { preserveState: true, replace: true });
}
function remove(admin: Admin) {
    if (admin.id === props.currentUserId) {
        window.alert('Akun yang sedang digunakan tidak dapat dihapus.');
        return;
    }
    if (window.confirm(`Hapus akun admin ${admin.name}?`)) {
        router.delete(`/admins/${admin.id}`, { preserveScroll: true });
    }
}
function initials(name: string) {
    return name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('');
}
function formatDate(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value));
}
</script>

<template>
    <Head title="Admin" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader eyebrow="Manajemen Pengguna" title="Admin" description="Kelola akun yang memiliki akses administrator Sport Center.">
            <Button @click="openCreate"><Plus class="mr-2 size-4" /> Tambah admin</Button>
        </PageHeader>

        <div class="rounded-2xl border bg-card p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Total admin</p>
                    <p class="mt-2 text-2xl font-semibold">{{ props.stats.total }}</p>
                </div>
                <ShieldCheck class="size-6 text-muted-foreground" />
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-col gap-3 border-b p-4 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="max-w-sm pl-9" placeholder="Cari nama atau email admin..." @keyup.enter="filter" />
                </div>
                <Button variant="secondary" @click="filter">Cari</Button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Admin</th>
                            <th class="px-4 py-3 font-medium">Role</th>
                            <th class="px-4 py-3 font-medium">Terdaftar</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="admin in props.admins.data" :key="admin.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary">{{ initials(admin.name) }}</div>
                                    <div><p class="font-medium">{{ admin.name }}</p><p class="text-xs text-muted-foreground">{{ admin.email }}</p></div>
                                    <span v-if="admin.id === props.currentUserId" class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary">Akun saya</span>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"><ShieldCheck class="size-3" /> Superadmin</span></td>
                            <td class="px-4 py-3 text-muted-foreground">{{ formatDate(admin.created_at) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" title="Edit" @click="openEdit(admin)"><Pencil class="size-4" /></Button>
                                    <Button variant="ghost" size="icon" title="Hapus" :disabled="admin.id === props.currentUserId || props.stats.total <= 1" @click="remove(admin)"><Trash2 class="size-4 text-destructive" /></Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.admins.data.length"><td colspan="4" class="px-4 py-12 text-center text-muted-foreground"><ShieldCheck class="mx-auto size-10 opacity-40" /><p class="mt-3 font-medium">Belum ada admin</p></td></tr>
                    </tbody>
                </table>
            </div>
            <div v-if="props.admins.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t p-4">
                <p class="text-xs text-muted-foreground">Halaman {{ props.admins.current_page }} dari {{ props.admins.last_page }} · {{ props.admins.total }} admin</p>
                <div class="flex flex-wrap gap-1">
                    <a v-for="link in props.admins.links" :key="link.label" :href="link.url ?? '#'" @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })">
                        <Button size="sm" variant="outline" :disabled="!link.url" :class="{ 'bg-primary text-primary-foreground': link.active }">{{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}</Button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeForm">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <div class="flex items-start justify-between border-b p-5">
                <div><p class="text-sm font-medium text-primary">User management</p><h2 class="mt-1 text-xl font-semibold">{{ title }}</h2></div>
                <Button variant="ghost" size="icon" @click="closeForm"><X class="size-4" /></Button>
            </div>
            <Form :key="formKey" :action="action" :method="method" class="space-y-4 p-5" @success="closeForm" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="admin-name">Nama admin</Label>
                    <Input id="admin-name" name="name" :default-value="editing?.name" required placeholder="Nama lengkap" />
                    <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
                </div>
                <div class="grid gap-2">
                    <Label for="admin-email">Email login</Label>
                    <Input id="admin-email" name="email" type="email" :default-value="editing?.email" required placeholder="admin@email.com" />
                    <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
                </div>
                <div class="grid gap-2">
                    <Label for="admin-password">Password</Label>
                    <Input id="admin-password" name="password" type="password" :required="!editing" :placeholder="editing ? 'Kosongkan jika tidak ingin mengganti password' : 'Minimal 8 karakter'" />
                    <p v-if="editing" class="text-xs text-muted-foreground">Password lama tidak ditampilkan. Isi hanya jika ingin menggantinya.</p>
                    <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeForm">Batal</Button>
                    <Button type="submit" :disabled="processing"><KeyRound class="mr-2 size-4" />{{ processing ? 'Menyimpan...' : editing ? 'Simpan perubahan' : 'Tambah admin' }}</Button>
                </div>
            </Form>
        </div>
    </div>
</template>
