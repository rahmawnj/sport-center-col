<script setup lang="ts">
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
    options: SelectOption[];
    name?: string;
    placeholder?: string;
    id?: string;
}>();

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="w-full">
        <Select v-model="model">
            <SelectTrigger :id="id" class="w-full">
                <SelectValue :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <input v-if="name" type="hidden" :name="name" :value="model" />
    </div>
</template>
