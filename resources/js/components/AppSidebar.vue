<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Building2,
    FileChartColumn,
    LayoutGrid,
    MapPin,
    Package as PackageIcon,
    Receipt,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

const items: (NavItem | NavGroup)[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Fasilitas', href: '/facilities', icon: Building2 },
    { title: 'Member', href: '/members', icon: Users },
    { title: 'Membership', href: '/memberships', icon: BadgeCheck },
    { title: 'Transaksi', href: '/transactions', icon: Receipt },
    {
        title: 'Laporan',
        icon: FileChartColumn,
        items: [
            {
                title: 'Penjualan Paket',
                href: '/reports/package',
                icon: PackageIcon,
            },
            {
                title: 'Booking Lapangan',
                href: '/reports/booking',
                icon: MapPin,
            },
            {
                title: 'Membership',
                href: '/reports/membership',
                icon: BadgeCheck,
            },
        ],
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()"><AppLogo /></Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="items" />
        </SidebarContent>
        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
