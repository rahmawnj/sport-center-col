<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    EllipsisVertical,
    Pencil,
    Plus,
    Search,
    Trash2,
    User,
    UserRound,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
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

type Gender = 'male' | 'female' | 'other';
type Member = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    member_code: string | null;
    qr_code: string | null;
    phone: string;
    date_of_birth: string | null;
    gender: Gender | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    memberships_count: number;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string };
    stats: { total: number; male: number; female: number };
};

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const showForm = ref(false);
const editing = ref<Member | null>(null);
const formKey = ref(0);
const form = ref({ gender: '' });
const title = computed(() => (editing.value ? 'Edit member' : 'Tambah member'));
const action = computed(() =>
    editing.value ? `/members/${editing.value.id}` : '/members',
);
const method = computed(() => (editing.value ? 'put' : 'post'));
const genderOptions = [
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
    { value: 'other', label: 'Lainnya' },
];
const genderLabel: Record<Gender, string> = {
    male: 'Laki-laki',
    female: 'Perempuan',
    other: 'Lainnya',
};
const statCards = computed(() => [
    {
        label: 'Total member',
        value: props.stats.total,
        icon: Users,
        accent: 'sky' as const,
    },
    {
        label: 'Laki-laki',
        value: props.stats.male,
        icon: User,
        accent: 'violet' as const,
    },
    {
        label: 'Perempuan',
        value: props.stats.female,
        icon: UserRound,
        accent: 'rose' as const,
    },
]);

function openCreate() {
    editing.value = null;
    form.value = { gender: '' };
    formKey.value++;
    showForm.value = true;
}

function openEdit(member: Member) {
    editing.value = member;
    form.value = { gender: member.gender ?? '' };
    formKey.value++;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
}

function filter() {
    router.get(
        '/members',
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function removeMember(member: Member) {
    if (!window.confirm(`Hapus member ${member.name}?`)) {
        return;
    }

    router.delete(`/members/${member.id}`, { preserveScroll: true });
}

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

function initials(value: string) {
    return value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase())
        .join('');
}
</script>

<template>
    <Head title="Member" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Member"
            description="Kelola data anggota Sport Center."
        >
            <Button @click="openCreate"
                ><Plus class="mr-2 size-4" /> Tambah member</Button
            >
        </PageHeader>

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            class="max-w-sm pl-9"
                            placeholder="Cari nama, email, atau kode member..."
                            @keyup.enter="filter"
                        />
                    </div>
                    <Button variant="secondary" @click="filter">Cari</Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 font-medium">Kode</th>
                            <th class="px-4 py-3 font-medium">No. HP</th>
                            <th class="px-4 py-3 font-medium">Gender</th>
                            <th class="px-4 py-3 font-medium">Tgl lahir</th>
                            <th class="px-4 py-3 font-medium">
                                Kontak darurat
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="row in props.members.data"
                            :key="row.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"
                                    >
                                        {{ initials(row.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">
                                            {{ row.name }}
                                        </div>
                                        <div
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ row.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    v-if="row.member_code"
                                    class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs"
                                >
                                    {{ row.member_code }}
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                                <div
                                    v-if="row.qr_code"
                                    class="mt-1 font-mono text-[10px] text-muted-foreground"
                                >
                                    QR: {{ row.qr_code }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ row.phone || '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ row.gender ? genderLabel[row.gender] : '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(row.date_of_birth) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template v-if="row.emergency_contact_name">
                                    {{ row.emergency_contact_name }}
                                    <span
                                        v-if="row.emergency_contact_phone"
                                        class="text-xs"
                                        >·
                                        {{ row.emergency_contact_phone }}</span
                                    >
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger :as-child="true">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Aksi"
                                                class="hover:bg-primary/10 hover:text-primary"
                                            >
                                                <EllipsisVertical
                                                    class="size-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-40"
                                        >
                                            <DropdownMenuItem
                                                @click="openEdit(row)"
                                            >
                                                <Pencil class="size-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                variant="destructive"
                                                @click="removeMember(row)"
                                            >
                                                <Trash2 class="size-4" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.members.data.length">
                            <td
                                colspan="7"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <Users
                                    class="mx-auto size-10 text-muted-foreground/50"
                                />
                                <p class="mt-3 font-medium">Belum ada member</p>
                                <p class="mt-1 text-sm">
                                    Tambahkan member baru untuk memulai.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="props.members.last_page > 1"
                class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ props.members.current_page }} dari
                    {{ props.members.last_page }} · {{ props.members.total }}
                    member
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in props.members.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        @click.prevent="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    >
                        <Button
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                        >
                            <span v-html="link.label" />
                        </Button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="showForm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeForm"
    >
        <div
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border bg-background shadow-2xl"
        >
            <div class="flex items-start justify-between border-b p-5">
                <div>
                    <p class="text-sm font-medium text-primary">
                        Manajemen member
                    </p>
                    <h2 class="mt-1 text-xl font-semibold">{{ title }}</h2>
                </div>
                <Button variant="ghost" size="icon" @click="closeForm"
                    ><X class="size-4"
                /></Button>
            </div>
            <Form
                :key="formKey"
                :action="action"
                :method="method"
                class="space-y-5 p-5"
                @success="closeForm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="member-name">Nama lengkap</Label>
                        <Input
                            id="member-name"
                            name="name"
                            :default-value="editing?.name"
                            required
                            placeholder="Nama member"
                        />
                        <p v-if="errors.name" class="text-xs text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-email">Email</Label>
                        <Input
                            id="member-email"
                            name="email"
                            type="email"
                            :default-value="editing?.email"
                            required
                            placeholder="email@contoh.com"
                        />
                        <p v-if="errors.email" class="text-xs text-destructive">
                            {{ errors.email }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-phone">No. HP</Label>
                        <Input
                            id="member-phone"
                            name="phone"
                            :default-value="editing?.phone ?? ''"
                            required
                            placeholder="08xxxxxxxxxx"
                        />
                        <p v-if="errors.phone" class="text-xs text-destructive">
                            {{ errors.phone }}
                        </p>
                    </div>
                    <div v-if="!editing" class="grid gap-2 sm:col-span-2">
                        <Label for="member-password">Password</Label>
                        <Input
                            id="member-password"
                            name="password"
                            type="password"
                            required
                            placeholder="Minimal 8 karakter"
                        />
                        <p
                            v-if="errors.password"
                            class="text-xs text-destructive"
                        >
                            {{ errors.password }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-code"
                            >Kode member / RFID (opsional)</Label
                        >
                        <Input
                            id="member-code"
                            name="member_code"
                            :default-value="editing?.member_code ?? ''"
                            placeholder="e.g. MBR-001"
                        />
                        <p
                            v-if="errors.member_code"
                            class="text-xs text-destructive"
                        >
                            {{ errors.member_code }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-dob">Tanggal lahir</Label>
                        <Input
                            id="member-dob"
                            name="date_of_birth"
                            type="date"
                            :default-value="editing?.date_of_birth ?? ''"
                        />
                        <p
                            v-if="errors.date_of_birth"
                            class="text-xs text-destructive"
                        >
                            {{ errors.date_of_birth }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-gender">Gender</Label>
                        <FormSelect
                            id="member-gender"
                            v-model="form.gender"
                            name="gender"
                            placeholder="Pilih gender"
                            :options="genderOptions"
                        />
                        <p
                            v-if="errors.gender"
                            class="text-xs text-destructive"
                        >
                            {{ errors.gender }}
                        </p>
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="member-address">Alamat</Label>
                        <textarea
                            id="member-address"
                            name="address"
                            rows="2"
                            :value="editing?.address ?? ''"
                            placeholder="Alamat member"
                            class="min-h-16 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        ></textarea>
                        <p
                            v-if="errors.address"
                            class="text-xs text-destructive"
                        >
                            {{ errors.address }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-ec-name">Kontak darurat</Label>
                        <Input
                            id="member-ec-name"
                            name="emergency_contact_name"
                            :default-value="
                                editing?.emergency_contact_name ?? ''
                            "
                            placeholder="Nama kontak darurat"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="member-ec-phone">No. HP darurat</Label>
                        <Input
                            id="member-ec-phone"
                            name="emergency_contact_phone"
                            :default-value="
                                editing?.emergency_contact_phone ?? ''
                            "
                            placeholder="08xxxxxxxxxx"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button type="button" variant="ghost" @click="closeForm"
                        >Batal</Button
                    >
                    <Button type="submit" :disabled="processing">
                        {{
                            processing
                                ? 'Menyimpan...'
                                : editing
                                  ? 'Simpan perubahan'
                                  : 'Tambah member'
                        }}
                    </Button>
                </div>
            </Form>
        </div>
    </div>
</template>
