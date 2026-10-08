<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { computed } from 'vue';

type Accent = 'primary' | 'emerald' | 'amber' | 'sky' | 'violet' | 'rose';

type SummaryCardItem = {
    label: string;
    value: string | number;
    icon?: LucideIcon;
    accent?: Accent;
};

const props = defineProps<{
    items: SummaryCardItem[];
}>();

const gridClass = computed(() =>
    props.items.length === 4
        ? 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4'
        : 'grid gap-4 sm:grid-cols-3',
);

const cards: Record<Accent, string> = {
    primary: 'bg-gradient-to-br from-orange-400 to-orange-600',
    emerald: 'bg-gradient-to-br from-emerald-400 to-teal-600',
    amber: 'bg-gradient-to-br from-amber-400 to-orange-500',
    sky: 'bg-gradient-to-br from-sky-400 to-blue-600',
    violet: 'bg-gradient-to-br from-violet-400 to-purple-600',
    rose: 'bg-gradient-to-br from-rose-400 to-pink-600',
};

const shadows: Record<Accent, string> = {
    primary: 'shadow-orange-500/30',
    emerald: 'shadow-emerald-500/30',
    amber: 'shadow-amber-500/30',
    sky: 'shadow-sky-500/30',
    violet: 'shadow-purple-500/30',
    rose: 'shadow-pink-500/30',
};
</script>

<template>
    <div :class="gridClass">
        <div
            v-for="item in items"
            :key="item.label"
            class="group relative overflow-hidden rounded-2xl p-5 transition-all hover:-translate-y-1"
            :class="
                item.accent
                    ? `${cards[item.accent]} ${shadows[item.accent]} text-white shadow-lg`
                    : 'border bg-card shadow-sm'
            "
        >
            <div
                v-if="item.accent"
                class="pointer-events-none absolute -top-8 -right-8 size-28 rounded-full bg-white/15 blur-2xl"
            ></div>

            <div class="relative flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="truncate text-xs font-medium tracking-wide uppercase"
                        :class="
                            item.accent
                                ? 'text-white/80'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ item.label }}
                    </p>
                    <p
                        class="mt-2 truncate text-2xl font-bold tracking-tight sm:text-3xl"
                    >
                        {{ item.value }}
                    </p>
                </div>
                <div
                    v-if="item.icon"
                    class="flex size-11 shrink-0 items-center justify-center rounded-2xl transition-transform group-hover:scale-105 sm:size-12"
                    :class="
                        item.accent
                            ? 'bg-white/20 text-white backdrop-blur-sm'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    <component :is="item.icon" class="size-5 sm:size-6" />
                </div>
            </div>
        </div>
    </div>
</template>
