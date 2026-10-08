<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Moon, Sun, User } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const { resolvedAppearance, updateAppearance } = useAppearance();

const toggleAppearance = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center gap-1">
            <Button
                variant="ghost"
                size="icon"
                title="Toggle dark mode"
                @click="toggleAppearance"
            >
                <Sun v-if="resolvedAppearance === 'dark'" class="size-5" />
                <Moon v-else class="size-5" />
                <span class="sr-only">Toggle dark mode</span>
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <Button
                        variant="ghost"
                        size="icon"
                        title="Akun"
                        class="rounded-full"
                    >
                        <User class="size-5" />
                        <span class="sr-only">Menu akun</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
