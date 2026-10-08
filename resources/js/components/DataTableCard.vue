<script setup lang="ts" generic="T">
import { router } from '@inertiajs/vue3';
import { computed, useSlots } from 'vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import type { TableColumn, TablePaginator } from '@/types';

defineProps<{
    columns: TableColumn[];
    rows: T[];
    paginator?: TablePaginator;
    totalLabel?: string;
}>();

defineSlots<{
    [name: string]: (props: { row: T; value: unknown }) => unknown;
    filters: () => unknown;
    footer: () => unknown;
    empty: () => unknown;
}>();

const slots = useSlots();
const cellSlots = computed(() =>
    Object.keys(slots).filter((name) => !['filters', 'footer'].includes(name)),
);
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div v-if="$slots.filters" class="border-b bg-muted/20 p-4">
            <slot name="filters" />
        </div>
        <DataTable :columns="columns" :rows="rows">
            <template v-for="name in cellSlots" :key="name" #[name]="slotProps">
                <slot :name="name" v-bind="slotProps ?? {}" />
            </template>
        </DataTable>
        <div
            v-if="paginator && paginator.last_page > 1"
            class="flex flex-col gap-3 border-t bg-muted/20 p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-xs text-muted-foreground">
                Halaman {{ paginator.current_page }} dari
                {{ paginator.last_page }} · {{ paginator.total }}
                {{ totalLabel ?? 'data' }}
            </p>
            <div class="flex flex-wrap gap-1">
                <a
                    v-for="link in paginator.links"
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
        <slot name="footer" />
    </div>
</template>
