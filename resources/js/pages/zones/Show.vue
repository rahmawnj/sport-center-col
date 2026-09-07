<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, CheckCircle2, CircleDot, Clock3, MapPin, Pencil, Users, XCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';

type ZoneSpace = { id: number; name: string; capacity: number | null; status: 'available' | 'maintenance'; created_at: string | null };
type Zone = { id: number; name: string; pricing_model: string; is_online_bookable: boolean; created_at: string | null; updated_at: string | null; zone_spaces: ZoneSpace[] };

const props = defineProps<{ zone: Zone }>();
const pricingLabel: Record<string, string> = { per_person: 'Per Person', per_space: 'Per Space', per_trainer_session: 'Per Trainer Session', per_table: 'Per Table' };
function date(value: string | null) { if (!value) return '—'; return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(value)); }
function initials(name: string) { return name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase(); }
</script>

<template>
    <Head :title="`${props.zone.name} · Zone`" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" as-child><Link href="/zones"><ArrowLeft class="size-4" /></Link></Button>
            <div><p class="text-sm text-muted-foreground">Zones / Detail</p><h1 class="text-xl font-semibold">Zone detail</h1></div>
        </div>

        <section class="overflow-hidden rounded-3xl border bg-card shadow-sm">
            <div class="h-28 bg-gradient-to-r from-primary/15 via-primary/5 to-transparent" />
            <div class="px-5 pb-6 md:px-7">
                <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-end gap-4">
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl border-4 border-background bg-primary/10 text-xl font-bold text-primary shadow-sm">{{ initials(props.zone.name) }}</div>
                        <div class="pb-1">
                            <div class="flex flex-wrap items-center gap-2"><h2 class="text-2xl font-semibold">{{ props.zone.name }}</h2><span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">{{ pricingLabel[props.zone.pricing_model] ?? props.zone.pricing_model }}</span></div>
                            <p class="mt-1 text-sm text-muted-foreground">Zone #{{ props.zone.id }} · Created {{ date(props.zone.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2"><Button variant="outline" as-child><Link href="/zones">Back to zones</Link></Button><Button as-child><Link :href="`/zones?edit=${props.zone.id}`"><Pencil class="mr-2 size-4" /> Edit zone</Link></Button></div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2"><CircleDot class="size-4 text-primary" /><h3 class="font-semibold">Zone information</h3></div>
                <div class="mt-5 space-y-4">
                    <div class="flex gap-3"><MapPin class="mt-0.5 size-4 text-muted-foreground" /><div><p class="text-xs text-muted-foreground">Zone name</p><p class="mt-1 text-sm font-medium">{{ props.zone.name }}</p></div></div>
                    <div class="flex gap-3"><Users class="mt-0.5 size-4 text-muted-foreground" /><div><p class="text-xs text-muted-foreground">Pricing model</p><p class="mt-1 text-sm font-medium">{{ pricingLabel[props.zone.pricing_model] ?? props.zone.pricing_model }}</p></div></div>
                    <div class="flex gap-3"><component :is="props.zone.is_online_bookable ? CheckCircle2 : XCircle" class="mt-0.5 size-4" :class="props.zone.is_online_bookable ? 'text-emerald-600' : 'text-muted-foreground'" /><div><p class="text-xs text-muted-foreground">Online booking</p><p class="mt-1 text-sm font-medium">{{ props.zone.is_online_bookable ? 'Bookable online' : 'Offline only' }}</p></div></div>
                    <div class="flex gap-3"><CalendarDays class="mt-0.5 size-4 text-muted-foreground" /><div><p class="text-xs text-muted-foreground">Created</p><p class="mt-1 text-sm font-medium">{{ date(props.zone.created_at) }}</p></div></div>
                    <div class="flex gap-3"><Clock3 class="mt-0.5 size-4 text-muted-foreground" /><div><p class="text-xs text-muted-foreground">Last updated</p><p class="mt-1 text-sm font-medium">{{ date(props.zone.updated_at) }}</p></div></div>
                </div>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3"><div><div class="flex items-center gap-2"><MapPin class="size-4 text-primary" /><h3 class="font-semibold">Zone spaces</h3></div><p class="mt-1 text-sm text-muted-foreground">{{ props.zone.zone_spaces.length }} space terdaftar di zone ini.</p></div></div>
                <div v-if="props.zone.zone_spaces.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div v-for="space in props.zone.zone_spaces" :key="space.id" class="rounded-xl border p-4">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-medium">{{ space.name }}</p><p class="mt-1 text-xs text-muted-foreground">Space #{{ space.id }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="space.status === 'available' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'">{{ space.status === 'available' ? 'Available' : 'Maintenance' }}</span></div>
                        <div class="mt-4 flex items-center gap-2 text-xs text-muted-foreground"><Users class="size-3.5" /> Capacity: <span class="font-medium text-foreground">{{ space.capacity ?? '—' }}</span></div>
                        <div class="mt-2 text-xs text-muted-foreground">Created {{ date(space.created_at) }}</div>
                    </div>
                </div>
                <div v-else class="mt-6 rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"><MapPin class="mx-auto size-8 opacity-50" /><p class="mt-2 font-medium">Belum ada zone space.</p><p class="mt-1">Zone ini belum memiliki area/space terdaftar.</p></div>
            </section>
        </div>
    </div>
</template>
