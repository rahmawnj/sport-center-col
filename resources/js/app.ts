import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import BookingLayout from '@/layouts/BookingLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Public landing page: no dashboard/sidebar layout.
            case name === 'Welcome':
            case name === 'booking/Landing':
                return null;

            // Public booking flow uses its own layout.
            case name === 'booking/Index':
                return BookingLayout;

            // Authentication pages.
            case name.startsWith('auth/'):
                return name === 'auth/Login' ? AuthSplitLayout : AuthLayout;

            // Dashboard settings keeps the authenticated dashboard layout.
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];

            // All other authenticated/dashboard pages.
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
