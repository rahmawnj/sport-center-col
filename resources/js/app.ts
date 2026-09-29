import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import LandingLayout from '@/layouts/LandingLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Public landing + booking pages use the same landing layout.
            case name === 'Welcome':
            case name.startsWith('booking/'):
                return LandingLayout;

            // Authentication pages.
            case name.startsWith('auth/'):
                return name === 'auth/Login' ? AuthSplitLayout : AuthLayout;

            // All remaining application pages use the dashboard layout.
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
