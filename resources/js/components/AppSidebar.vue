<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, CalendarCheck, CircleDollarSign, CircleDot, Dumbbell, FolderGit2, LayoutGrid, Users, PanelsTopLeft, Building2, PackagePlus, CreditCard, UserRoundPlus, Repeat2 } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const masterDataItems: NavItem[] = [
    { title: 'Users', href: '/users', icon: Users },
    { title: 'Zones', href: '/zones', icon: CircleDot },
    { title: 'Zone Spaces', href: '/zone-spaces', icon: PanelsTopLeft },
    { title: 'Pricing Rates', href: '/pricing-rates', icon: CircleDollarSign },
    { title: 'Membership Packages', href: '/membership-packages', icon: CreditCard },
    { title: 'Trainers', href: '/trainers', icon: Dumbbell },
    { title: 'Facilities', href: '/facilities', icon: Building2 },
    { title: 'Add-ons', href: '/add-ons', icon: PackagePlus },
];

const navigationItems: NavItem[] = [
    { title: 'Booking', href: '/bookings', icon: CalendarCheck },
    { title: 'Membership', href: '/memberships', icon: UserRoundPlus },
    { title: 'Subscription', href: '/subscriptions', icon: Repeat2 },
];

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/rahmawnj/sport-center-col', icon: FolderGit2 },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
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
            <SidebarMenu class="px-2 py-0">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child :tooltip="'Dashboard'">
                        <Link :href="dashboard()"><LayoutGrid /><span>Dashboard</span></Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavMain :items="navigationItems" label="Operations" />
            <NavMain :items="masterDataItems" label="Master Data" />
        </SidebarContent>
        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
