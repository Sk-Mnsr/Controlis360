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
                    :to="{ name: 'gouvernance-it.govstrat-itr' }"
                    class="rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-400 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Sous-module</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">GovStrat IT-R</p>
                    <p class="mt-1 text-sm text-slate-700">
                        Gouvernance stratégique IT et pilotage des risques informatiques.
                    </p>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
</script>
