<script setup lang="ts">
defineProps<{
    title: string;
    eyebrow?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div>
            <p v-if="eyebrow" class="text-sm font-medium text-primary">
                {{ eyebrow }}
            </p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
