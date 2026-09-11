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
    { title: 'Pengguna', href: '/users', icon: Users },
    { title: 'Zona', href: '/zones', icon: CircleDot },
    { title: 'Area Lapangan', href: '/zone-spaces', icon: PanelsTopLeft },
    { title: 'Tarif Harga', href: '/pricing-rates', icon: CircleDollarSign },
    { title: 'Paket Keanggotaan', href: '/membership-packages', icon: CreditCard },
    { title: 'Pelatih', href: '/trainers', icon: Dumbbell },
    { title: 'Fasilitas', href: '/facilities', icon: Building2 },
    { title: 'Layanan Tambahan', href: '/add-ons', icon: PackagePlus },
];

const navigationItems: NavItem[] = [
    { title: 'Pemesanan', href: '/bookings', icon: CalendarCheck },
    { title: 'Keanggotaan', href: '/memberships', icon: UserRoundPlus },
    { title: 'Langganan', href: '/subscriptions', icon: Repeat2 },
];

const footerNavItems: NavItem[] = [
    { title: 'Repositori', href: 'https://github.com/rahmawnj/sport-center-col', icon: FolderGit2 },
    { title: 'Dokumentasi', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
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
                    <SidebarMenuButton as-child :tooltip="'Dasbor'">
                        <Link :href="dashboard()"><LayoutGrid /><span>Dasbor</span></Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavMain :items="navigationItems" label="Operasional" />
            <NavMain :items="masterDataItems" label="Data Master" />
        </SidebarContent>
        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
