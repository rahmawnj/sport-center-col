<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    CreditCard,
    Mail,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import PageHeader from '@/components/PageHeader.vue';

type MembershipStatus = 'active' | 'expired' | 'cancelled';
type Props = {
    membership: {
        id: number;
        status: MembershipStatus;
        start_date: string | null;
        end_date: string | null;
        days_left: number;
        member: {
            name: string | null;
            email: string | null;
            member_code: string | null;
            qr_code: string | null;
        };
        package: {
            name: string | null;
            type: string | null;
            duration_days: number | null;
            price: number;
        };
    };
};

const props = defineProps<Props>();

const statusMeta: Record<
    MembershipStatus,
    { label: string; badge: string; dot: string; gradient: string }
> = {
    active: {
        label: 'Aktif',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
        gradient: 'from-emerald-400 to-teal-600',
    },
    expired: {
        label: 'Kedaluwarsa',
        badge: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
        dot: 'bg-rose-500',
        gradient: 'from-rose-400 to-pink-600',
    },
    cancelled: {
        label: 'Dibatalkan',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
        gradient: 'from-slate-400 to-slate-600',
    },
};

const packageTypeLabel: Record<string, string> = {
    session: 'Sesi',
    membership: 'Keanggotaan',
    entry: 'Harian',
};

const meta = computed(() => props.membership);
const status = computed(() => statusMeta[meta.value.status]);

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value));
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head :title="`Membership · ${meta.member.name ?? ''}`" />
    <div class="space-y-6 p-4 md:p-6">
        <Link
            href="/memberships"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke membership
        </Link>

        <PageHeader
            eyebrow="Detail membership"
            :title="meta.member.name ?? '—'"
            :description="meta.package.name ?? ''"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium"
                :class="status.badge"
            >
                <span class="size-1.5 rounded-full" :class="status.dot" />
                {{ status.label }}
            </span>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- QR kartu member -->
            <div
                class="flex flex-col items-center gap-4 rounded-2xl border bg-card p-6 text-center shadow-sm"
            >
                <div class="rounded-2xl bg-white p-4 shadow-inner">
                    <QrcodeVue
                        :value="meta.member.qr_code ?? ''"
                        :size="180"
                        level="M"
                        render-as="svg"
                    />
                </div>
                <div>
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        QR kartu member
                    </p>
                    <p class="mt-1 font-mono text-sm font-semibold">
                        {{ meta.member.member_code || '—' }}
                    </p>
                </div>
                <p class="text-xs text-muted-foreground">
                    Scan QR ini di pintu masuk untuk check-in.
                </p>
            </div>

            <!-- Info -->
            <div class="space-y-4 lg:col-span-2">
                <!-- Package -->
                <div
                    class="relative overflow-hidden rounded-2xl bg-gradient-to-br p-5 text-white shadow-lg"
                    :class="status.gradient"
                >
                    <div
                        class="pointer-events-none absolute -top-8 -right-8 size-28 rounded-full bg-white/15 blur-2xl"
                    ></div>
                    <p
                        class="relative text-xs font-medium tracking-wide uppercase"
                    >
                        Paket membership
                    </p>
                    <p class="relative mt-2 text-2xl font-bold tracking-tight">
                        {{ meta.package.name || '—' }}
                    </p>
                    <div
                        class="relative mt-4 flex flex-wrap items-center gap-2 text-sm"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 backdrop-blur-sm"
                        >
                            <BadgeCheck class="size-3.5" />
                            {{
                                meta.package.type
                                    ? (packageTypeLabel[meta.package.type] ??
                                      meta.package.type)
                                    : '—'
                            }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 backdrop-blur-sm"
                        >
                            <CalendarDays class="size-3.5" />
                            {{ meta.package.duration_days ?? '—' }} hari
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 backdrop-blur-sm"
                        >
                            <CreditCard class="size-3.5" />
                            {{ formatIDR(meta.package.price) }}
                        </span>
                    </div>
                </div>

                <!-- Meta -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                        >
                            <User class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted-foreground">
                                Member
                            </div>
                            <div class="truncate font-medium">
                                {{ meta.member.name || '—' }}
                            </div>
                            <div
                                v-if="meta.member.member_code"
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ meta.member.member_code }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400"
                        >
                            <Mail class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted-foreground">
                                Email
                            </div>
                            <div class="truncate font-medium">
                                {{ meta.member.email || '—' }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <CalendarDays class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted-foreground">
                                Periode
                            </div>
                            <div class="font-medium">
                                {{ formatDate(meta.start_date) }} –
                                {{ formatDate(meta.end_date) }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                        >
                            <BadgeCheck class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-muted-foreground">
                                Sisa masa aktif
                            </div>
                            <div class="font-medium">
                                {{
                                    meta.status === 'active'
                                        ? `${meta.days_left} hari`
                                        : '—'
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
