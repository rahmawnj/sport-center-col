<script setup lang="ts">
import { Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type SelectOption = {
    value: string;
    label: string;
};

defineProps<{
    searchPlaceholder?: string;
    statusOptions?: SelectOption[];
}>();

const search = defineModel<string>('search', { default: '' });
const status = defineModel<string>('status', { default: 'all' });

const emit = defineEmits<{
    filter: [];
    reset: [];
}>();
</script>

<template>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                class="max-w-sm pl-9"
                :placeholder="searchPlaceholder ?? 'Cari...'"
                @keyup.enter="emit('filter')"
            />
        </div>
        <Select v-if="statusOptions?.length" v-model="status">
            <SelectTrigger class="w-full lg:w-48">
                <SelectValue placeholder="Pilih filter" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <Button variant="secondary" @click="emit('filter')">Filter</Button>
        <Button variant="ghost" @click="emit('reset')">Reset</Button>
    </div>
</template>
