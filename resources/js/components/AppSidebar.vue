import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Building2,
    Dumbbell,
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
    { title: 'Olahraga', href: '/facilities', icon: Building2 },
    { title: 'Member', href: '/members', icon: Users },
    { title: 'Membership', href: '/memberships', icon: BadgeCheck },
    { title: 'Paket Membership', href: '/membership-packages', icon: PackageIcon },
    { title: 'Trainer', href: '/trainers', icon: Dumbbell },
    { title: 'Paket Trainer', href: '/trainer-packages', icon: PackageIcon },
    { title: 'Membership Trainer', href: '/membership-trainers', icon: Dumbbell },
    { title: 'Transaksi', href: '/transactions', icon: Receipt },
    {
        title: 'Laporan',
        icon: FileChartColumn,
        items: [
            { title: 'Pembelian Membership', href: '/reports/membership', icon: BadgeCheck },
            { title: 'Pembelian Paket Trainer', href: '/reports/trainer', icon: Dumbbell },
            { title: 'Pembelian Booking Lapangan', href: '/reports/booking', icon: MapPin },
            { title: 'Pembelian Kelas', href: '/reports/class', icon: Users },
        ],
    },
];

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
