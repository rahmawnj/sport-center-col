<script setup lang="ts" generic="T">
import type { TableAlign, TableColumn } from '@/types';

defineProps<{
    columns: TableColumn[];
    rows: T[];
}>();

defineSlots<{
    [name: string]: (props: { row: T; value: unknown }) => unknown;
    empty: () => unknown;
}>();

const alignClass: Record<TableAlign, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead
                class="border-b border-border bg-muted/80 text-left text-[11px] tracking-wider text-muted-foreground uppercase"
            >
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        scope="col"
                        :class="[
                            'px-5 py-3.5 font-semibold',
                            alignClass[column.align ?? 'left'],
                            column.class,
                        ]"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60">
                <tr
                    v-for="(row, index) in rows"
                    :key="index"
                    class="group transition-colors even:bg-muted/40 hover:bg-primary/5"
                >
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        :class="[
                            'px-5 py-4',
                            alignClass[column.align ?? 'left'],
                            column.class,
                        ]"
                    >
                        <slot
                            :name="column.key"
                            :row="row"
                            :value="
                                (row as Record<string, unknown>)[column.key]
                            "
                        />
                    </td>
                </tr>
                <tr v-if="rows.length === 0">
                    <td
                        :colspan="columns.length"
                        class="px-5 py-16 text-center"
                    >
                        <slot name="empty" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
