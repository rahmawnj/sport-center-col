<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    Clock3,
    Mail,
    MapPin,
    Phone,
    ShieldCheck,
    UserRound,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import PageHeader from '@/components/PageHeader.vue';

type MembershipStatus = 'active' | 'expired' | 'cancelled';
type Props = {
    member: {
        id: number;
        user_id: number;
        name: string;
        email: string;
        member_code: string | null;
        qr_code: string | null;
        phone: string | null;
        date_of_birth: string | null;
        gender: 'male' | 'female' | 'other' | null;
        address: string | null;
        face_photo_path: string | null;
        emergency_contact_name: string | null;
        emergency_contact_phone: string | null;
        registered_at: string | null;
        memberships_count: number;
        active_memberships: {
            id: number; package_name: string; facility_name: string; start_date: string | null; end_date: string | null;
        }[];
        active_trainer_packages: {
            id: number; package_name: string; facility_name: string; trainer_name: string;
            start_date: string | null; expires_at: string | null; total_sessions: number; used_sessions: number; remaining_sessions: number;
        }[];
        memberships: {
            id: number;
            package_name: string;
            start_date: string | null;
            end_date: string | null;
            status: MembershipStatus;
        }[];
    };
};

const props = defineProps<Props>();
const genderLabel: Record<string, string> = {
    male: 'Laki-laki',
    female: 'Perempuan',
    other: 'Lainnya',
};
const statusMeta: Record<MembershipStatus, { label: string; classes: string }> = {
    active: { label: 'Aktif', classes: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' },
    expired: { label: 'Kedaluwarsa', classes: 'bg-rose-500/10 text-rose-600 dark:text-rose-400' },
    cancelled: { label: 'Dibatalkan', classes: 'bg-muted text-muted-foreground' },
};
const activeMemberships = computed(
    () => props.member.memberships.filter((item) => item.status === 'active').length,
);

function formatDate(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <Head :title="`Detail Member · ${member.name}`" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Data Master"
            title="Detail Member"
            description="Informasi lengkap anggota dan riwayat membership Sport Center."
        >
            <Link
                href="/members"
                class="inline-flex h-10 items-center gap-2 rounded-lg border bg-background px-4 text-sm font-medium transition-colors hover:bg-muted"
            >
                <ArrowLeft class="size-4" />
                Kembali
            </Link>
        </PageHeader>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)]">
            <div class="space-y-6">
                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="flex flex-col gap-5 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-6 sm:flex-row sm:items-center">
                        <div v-if="member.face_photo_path" class="size-16 shrink-0 overflow-hidden rounded-2xl bg-muted"><img :src="'/storage/' + member.face_photo_path" :alt="'Foto wajah ' + member.name" class="size-full object-cover" /></div>
                        <div v-else class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-primary text-xl font-bold text-primary-foreground">
                            {{ member.name.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold tracking-wider text-primary uppercase">Profil Anggota</p>
                            <h2 class="mt-1 truncate text-2xl font-bold">{{ member.name }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ member.email }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-background px-3 py-1 font-mono text-xs">{{ member.member_code || 'Kode belum tersedia' }}</span>
                                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">Member terdaftar</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-px bg-border sm:grid-cols-2 lg:grid-cols-3">
                        <div class="bg-card p-5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground"><Phone class="size-4" /> Nomor HP</div>
                            <p class="mt-2 font-semibold">{{ member.phone || '—' }}</p>
                        </div>
                        <div class="bg-card p-5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground"><UserRound class="size-4" /> Jenis kelamin</div>
                            <p class="mt-2 font-semibold">{{ member.gender ? genderLabel[member.gender] : '—' }}</p>
                        </div>
                        <div class="bg-card p-5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground"><CalendarDays class="size-4" /> Tanggal lahir</div>
                            <p class="mt-2 font-semibold">{{ formatDate(member.date_of_birth) }}</p>
                        </div>
                        <div class="bg-card p-5 sm:col-span-2 lg:col-span-3">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground"><MapPin class="size-4" /> Alamat</div>
                            <p class="mt-2 leading-relaxed font-medium">{{ member.address || 'Alamat belum diisi.' }}</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b p-5">
                        <div>
                            <h3 class="font-semibold">Kontak Darurat</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Kontak yang dapat dihubungi saat keadaan darurat.</p>
                        </div>
                        <ShieldCheck class="size-5 text-muted-foreground" />
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Nama kontak</p>
                            <p class="mt-1 font-medium">{{ member.emergency_contact_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Nomor telepon</p>
                            <p class="mt-1 font-medium">{{ member.emergency_contact_phone || '—' }}</p>
                        </div>
                    </div>
                </section>

                <section v-if="member.active_memberships.length" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="border-b p-5">
                        <h3 class="font-semibold">Paket Membership Aktif</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Paket keanggotaan yang sedang berlaku saat ini.</p>
                    </div>
                    <div class="divide-y">
                        <div v-for="item in member.active_memberships" :key="item.id" class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">{{ item.package_name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ item.facility_name }} · {{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</p>
                            </div>
                            <span class="w-fit rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600">Aktif</span>
                        </div>
                    </div>
                </section>

                <section v-if="member.active_trainer_packages.length" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="border-b p-5">
                        <h3 class="font-semibold">Paket Personal Trainer Aktif</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Paket latihan personal yang masih berlaku dan memiliki sisa sesi.</p>
                    </div>
                    <div class="divide-y">
                        <div v-for="item in member.active_trainer_packages" :key="item.id" class="space-y-3 p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">{{ item.package_name }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ item.facility_name }} · Pelatih: {{ item.trainer_name }}</p>
                                </div>
                                <span class="w-fit rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600">Aktif</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="text-muted-foreground">{{ formatDate(item.start_date) }} – {{ formatDate(item.expires_at) }}</span>
                                <span class="font-semibold tabular-nums">{{ item.remaining_sessions }} / {{ item.total_sessions }} sesi tersisa</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary" :style="{ width: (item.total_sessions ? item.used_sessions / item.total_sessions * 100 : 0) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="flex flex-col gap-2 border-b p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold">Riwayat Membership</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Daftar paket yang pernah atau sedang dimiliki member.</p>
                        </div>
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{{ member.memberships_count }} membership</span>
                    </div>
                    <div v-if="member.memberships.length" class="divide-y">
                        <Link
                            v-for="item in member.memberships"
                            :key="item.id"
                            :href="`/memberships/${item.id}`"
                            class="flex flex-col gap-3 p-5 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <BadgeCheck class="size-5" />
                                </div>
                                <div>
                                    <p class="font-semibold">{{ item.package_name }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ formatDate(item.start_date) }} – {{ formatDate(item.end_date) }}</p>
                                </div>
                            </div>
                            <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold" :class="statusMeta[item.status].classes">{{ statusMeta[item.status].label }}</span>
                        </Link>
                    </div>
                    <div v-else class="px-5 py-12 text-center text-sm text-muted-foreground">
                        <Users class="mx-auto mb-3 size-9 opacity-40" />
                        Member ini belum memiliki riwayat membership.
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="border-b p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">Foto Wajah Member</h3>
                                <p class="mt-1 text-xs text-muted-foreground">Foto yang tersimpan untuk identifikasi Face Recognition.</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="member.face_photo_path ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-muted text-muted-foreground'"
                            >
                                {{ member.face_photo_path ? 'Terdaftar' : 'Belum ada foto' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        <div v-if="member.face_photo_path" class="overflow-hidden rounded-xl border bg-muted">
                            <img
                                :src="'/storage/' + member.face_photo_path"
                                :alt="'Foto wajah ' + member.name"
                                class="aspect-[4/5] w-full object-cover"
                            />
                        </div>
                        <div v-else class="flex aspect-[4/5] flex-col items-center justify-center rounded-xl border border-dashed bg-muted/30 px-6 text-center">
                            <UserRound class="size-12 text-muted-foreground/40" />
                            <p class="mt-4 font-medium">Foto wajah belum tersedia</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">Ambil dan simpan foto melalui halaman registrasi FaceRec untuk menampilkannya di sini.</p>
                        </div>
                        <p v-if="member.face_photo_path" class="mt-3 text-xs text-muted-foreground">
                            Foto tersimpan di server dan terhubung dengan profil member ini.
                        </p>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-6 text-center shadow-sm">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <BadgeCheck class="size-6" />
                    </div>
                    <h3 class="mt-3 font-semibold">QR Code Member</h3>
                    <p class="mt-1 text-xs text-muted-foreground">Gunakan QR ini untuk identifikasi anggota.</p>
                    <div v-if="member.qr_code" class="mx-auto mt-5 inline-block rounded-2xl bg-white p-4 shadow-inner">
                        <QrcodeVue :value="member.qr_code" :size="190" level="M" render-as="svg" />
                    </div>
                    <div v-else class="mt-5 rounded-xl bg-muted p-8 text-sm text-muted-foreground">QR Code belum tersedia.</div>
                    <p class="mt-3 break-all font-mono text-xs text-muted-foreground">{{ member.qr_code || '—' }}</p>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="font-semibold">Ringkasan Member</h3>
                    <div class="mt-4 space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Total membership</span>
                            <span class="font-semibold tabular-nums">{{ member.memberships_count }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Membership aktif</span>
                            <span class="font-semibold tabular-nums text-emerald-600">{{ activeMemberships }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Terdaftar sejak</span>
                            <span class="text-right text-sm font-medium">{{ formatDate(member.registered_at?.slice(0, 10) ?? null) }}</span>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center gap-2 border-t pt-4 text-xs text-muted-foreground">
                        <Clock3 class="size-4" />
                        ID Member: #{{ member.id }}
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
