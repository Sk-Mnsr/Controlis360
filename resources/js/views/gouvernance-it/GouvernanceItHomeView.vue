<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-900">Gouvernance IT</h2>
            <p class="mt-2 text-slate-600">
                Bienvenue, <strong>{{ auth.user?.name }}</strong>.
                Vous êtes connecté en tant que <strong>{{ auth.user?.profile_fr }}</strong>
                <span v-if="auth.user?.gouvernance_it_role_fr">
                    ({{ auth.user.gouvernance_it_role_fr }})
                </span>.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RouterLink
                    v-for="item in subModules"
                    :key="item.route"
                    :to="{ name: item.route }"
                    class="rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-400 hover:shadow-sm"
                >
                    <p class="text-lg font-semibold text-slate-950">{{ item.title }}</p>
                    <p class="mt-1 text-sm text-slate-700">{{ item.description }}</p>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const subModules = [
    {
        title: 'GovStrat IT-R',
        description: 'Gouvernance stratégique IT et pilotage des risques informatiques.',
        route: 'gouvernance-it.govstrat-itr',
    },
    {
        title: 'Cartographie des applications',
        description: 'Inventaire et cartographie du patrimoine applicatif.',
        route: 'gouvernance-it.cartographie-applications',
    },
    {
        title: 'Registre de comptes génériques',
        description: 'Suivi et gestion des comptes génériques du système d’information.',
        route: 'gouvernance-it.registre-comptes-generiques',
    },
];
</script>
