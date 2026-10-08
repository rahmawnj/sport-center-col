<script setup lang="ts">
import { Dumbbell } from '@lucide/vue';

type SportOption = {
    id: number;
    name: string;
};

defineProps<{
    sports: SportOption[];
}>();

const selected = defineModel<string>('modelValue', { default: 'all' });

const emit = defineEmits<{
    change: [];
}>();

function select(value: string) {
    selected.value = value;
    emit('change');
}
</script>

<template>
    <div class="rounded-2xl border bg-card p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <Dumbbell class="size-4 text-primary" />
            <p class="text-sm font-medium">Filter berdasarkan olahraga</p>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <button
                type="button"
                :aria-pressed="selected === 'all'"
                class="cursor-pointer rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors"
                :class="
                    selected === 'all'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border hover:bg-muted'
                "
                @click="select('all')"
            >
                Semua olahraga
            </button>
            <button
                v-for="sport in sports"
                :key="sport.id"
                type="button"
                :aria-pressed="selected === String(sport.id)"
                class="cursor-pointer rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors"
                :class="
                    selected === String(sport.id)
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border hover:bg-muted'
                "
                @click="select(String(sport.id))"
            >
                {{ sport.name }}
            </button>
            <p v-if="sports.length === 0" class="text-sm text-muted-foreground">
                Belum ada olahraga. Tambahkan olahraga terlebih dahulu.
            </p>
        </div>
    </div>
</template>
