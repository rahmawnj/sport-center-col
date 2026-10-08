<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { isNavGroup } from '@/types';
import type { NavGroup, NavItem } from '@/types';

defineProps<{
    items: (NavItem | NavGroup)[];
    label?: string;
}>();

const { isCurrentOrParentUrl } = useCurrentUrl();

const isGroupActive = (group: NavGroup) =>
    group.items.some((child) => isCurrentOrParentUrl(child.href));
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible
                v-for="item in items"
                :key="item.title"
                as-child
                class="group/collapsible"
                :default-open="isNavGroup(item) && isGroupActive(item)"
            >
                <SidebarMenuItem>
                    <SidebarMenuButton
                        v-if="!isNavGroup(item)"
                        as-child
                        :is-active="isCurrentOrParentUrl(item.href)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                    <template v-else>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :is-active="isGroupActive(item)"
                                :tooltip="item.title"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="child in item.items"
                                    :key="child.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="
                                            isCurrentOrParentUrl(child.href)
                                        "
                                    >
                                        <Link :href="child.href">
                                            <component :is="child.icon" />
                                            <span>{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </template>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
