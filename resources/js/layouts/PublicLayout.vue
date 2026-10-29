<script setup lang="ts">
import LandingFooter from '@/components/LandingFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
</script>

<template>
    <div class="public-site min-h-screen bg-white text-[#001428]">
        <LandingHeader />

        <slot />

        <LandingFooter />
    </div>
</template>

<style>
.public-site {
    --sport-lime: #a4da01;
    --sport-navy: #001428;
    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
.public-site .brand-mark {
    display: grid;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    place-items: center;
    border-radius: 12px;
    background: var(--sport-lime);
    color: var(--sport-navy);
    font-size: 13px;
    font-weight: 950;
    letter-spacing: -.06em;
}
.public-site .brand-accent { color: #8dbb00; }
.public-site .public-primary-button {
    background: var(--sport-lime);
    color: var(--sport-navy);
    transition: transform .2s ease, background .2s ease;
}
.public-site .public-primary-button:hover {
    transform: translateY(-1px);
    background: #b8ec24;
}
.public-site .public-book-link { color: var(--sport-navy); }
.public-site .public-book-link:hover { background: #f1f5f9; }
.public-site .public-nav-link {
    position: relative;
    padding: 10px 0;
    transition: color .2s ease;
}
.public-site .public-nav-link:hover { color: #729700; }
.public-site .public-nav-link::after {
    position: absolute;
    right: 0;
    bottom: 2px;
    left: 0;
    height: 2px;
    background: var(--sport-lime);
    content: '';
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .2s ease;
}
.public-site .public-nav-link:hover::after { transform: scaleX(1); }
@media (max-width: 420px) {
    .public-site .brand-mark { width: 32px; height: 32px; border-radius: 10px; }
    .public-site .public-book-link { padding-right: .65rem; padding-left: .65rem; }
}
</style>
