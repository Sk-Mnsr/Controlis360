<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Suivi des recommandations</h2>
            <p class="mt-2 text-slate-600">
                Bienvenue, <strong>{{ auth.user?.name }}</strong>.
                Vous êtes connecté en tant que <strong>{{ roleLabel }}</strong>.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RouterLink
                    v-if="!isRegulatorOnly"
                    :to="{ name: 'audit.missions' }"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 transition hover:border-emerald-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Module</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-950">Missions</p>
                    <p class="mt-1 text-sm text-emerald-900">
                        {{ missionsCardDescription }}
                    </p>
                </RouterLink>

                <RouterLink
                    v-if="showRegulatorCard"
                    :to="{ name: 'audit.regulator' }"
                    class="rounded-xl border border-blue-200 bg-blue-50 p-5 transition hover:border-blue-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-800">Régulateur</p>
                    <p class="mt-2 text-lg font-semibold text-blue-950">File d'avis</p>
                    <p class="mt-1 text-sm text-blue-900">
                        Consulter les recommandations transmises et formuler un avis avant clôture.
                    </p>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { isRegulatorProfile } from '../../config/module-access';

const auth = useAuthStore();

const roleLabel = computed(() => {
    const user = auth.user;
    if (!user) return '';

    if (user.controle_role_fr) {
        return `${user.profile_fr} — ${user.controle_role_fr}`;
    }

    if (user.audit_role_fr) {
        return `${user.profile_fr} — ${user.audit_role_fr}`;
    }

    if (user.metier_role_fr) {
        return `${user.profile_fr} — ${user.metier_role_fr}`;
    }

    return user.profile_fr ?? '';
});

const missionsCardDescription = computed(() => {
    if (auth.user?.profile === 'metier' && auth.user?.metier_role === 'responsable_entite') {
        return 'Consulter les missions qui vous ont été adressées.';
    }

    return 'Consulter et créer des missions de suivi des recommandations.';
});

const isRegulatorOnly = computed(() => auth.user?.profile === 'regulateur');

const showRegulatorCard = computed(() => isRegulatorProfile(auth.user?.profile));
</script>
