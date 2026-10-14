<template>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="h-1.5 bg-gradient-to-r from-[#c00000] via-[#e11d48] to-[#047857]" />
            <div class="flex flex-col gap-4 bg-gradient-to-br from-red-50/80 via-white to-slate-50 px-6 py-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#c00000]">Gouvernance IT</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Accueil</h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600">
                        Pilotage stratégique, patrimoine applicatif et comptes génériques du système d’information.
                    </p>
                </div>
                <p class="shrink-0 rounded-full border border-white bg-white/90 px-3 py-1.5 text-sm text-slate-700 shadow-sm">
                    <span class="font-semibold text-slate-900">{{ auth.user?.name }}</span>
                    <span class="text-slate-400"> · </span>
                    {{ roleLabel }}
                </p>
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            <RouterLink
                v-for="item in subModules"
                :key="item.route"
                :to="{ name: item.route }"
                class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <span class="absolute inset-x-0 top-0 h-1" :class="item.barClass" />
                <span
                    class="inline-flex h-12 w-12 shrink-0 items-center justify-center self-start rounded-2xl text-white shadow-sm"
                    :class="item.iconClass"
                >
                    <svg v-if="item.key === 'govstrat'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3.5v5.2c0 4.6-3 7.4-7 8.8-4-1.4-7-4.2-7-8.8V6.5L12 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                    </svg>
                    <svg v-else-if="item.key === 'cartographie'" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <rect x="4" y="4" width="6.5" height="6.5" rx="1.2" />
                        <rect x="13.5" y="4" width="6.5" height="6.5" rx="1.2" />
                        <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.2" />
                        <rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.2" />
                    </svg>
                    <svg v-else class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <circle cx="9" cy="8" r="2.4" />
                        <circle cx="16" cy="9" r="1.8" />
                        <path stroke-linecap="round" d="M4.5 18.5c.6-2.6 2.6-4 4.5-4s3.9 1.4 4.5 4" />
                        <path stroke-linecap="round" d="M14 14.8c1.2-.5 2.4-.6 3.4-.2 1.3.5 2.2 1.8 2.6 3.9" />
                    </svg>
                </span>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ item.title }}</h3>
                <p class="mt-1 flex-1 text-sm leading-relaxed text-slate-600">{{ item.description }}</p>
                <span class="mt-5 inline-flex items-center gap-1 self-start rounded-full px-3 py-1 text-sm font-semibold" :class="item.linkClass">
                    Ouvrir
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                    </svg>
                </span>
            </RouterLink>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const roleLabel = computed(() => {
    const user = auth.user;
    if (!user) return '';
    if (user.gouvernance_it_role_fr) {
        return `${user.profile_fr} — ${user.gouvernance_it_role_fr}`;
    }
    return user.profile_fr ?? '';
});

const subModules = [
    {
        key: 'govstrat',
        title: 'GovStrat IT-R',
        description: 'Gouvernance stratégique IT et pilotage des risques informatiques.',
        route: 'gouvernance-it.govstrat-itr',
        barClass: 'bg-red-700',
        iconClass: 'bg-red-700',
        linkClass: 'bg-red-50 text-red-700',
    },
    {
        key: 'cartographie',
        title: 'Cartographie des applications',
        description: 'Inventaire et cartographie du patrimoine applicatif.',
        route: 'gouvernance-it.cartographie-applications',
        barClass: 'bg-slate-700',
        iconClass: 'bg-slate-800',
        linkClass: 'bg-slate-100 text-slate-800',
    },
    {
        key: 'registre',
        title: 'Registre de comptes génériques',
        description: 'Suivi et gestion des comptes génériques du système d’information.',
        route: 'gouvernance-it.registre-comptes-generiques',
        barClass: 'bg-emerald-600',
        iconClass: 'bg-emerald-600',
        linkClass: 'bg-emerald-50 text-emerald-800',
    },
];
</script>
