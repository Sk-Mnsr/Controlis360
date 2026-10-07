<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                Module indépendant
            </p>
            <h2 class="mt-1 text-xl font-semibold text-slate-900">Vente aux enchères</h2>
            <p class="mt-2 text-slate-600">
                Bienvenue, <strong>{{ auth.user?.name }}</strong>.
                Vous êtes connecté en tant que <strong>{{ auth.user?.profile_fr }}</strong>.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <RouterLink
                    v-if="canAccessClient"
                    :to="{ name: 'vente-encheres.client.home' }"
                    class="rounded-xl border border-sky-200 bg-sky-50 p-5 transition hover:border-sky-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-800">Espace</p>
                    <p class="mt-2 text-lg font-semibold text-sky-950">Client</p>
                    <p class="mt-1 text-sm text-sky-900">
                        Front public : recherche, catégories, enchères, compte utilisateur.
                    </p>
                </RouterLink>

                <RouterLink
                    v-if="canAccessComite"
                    :to="{ name: 'vente-encheres.comite' }"
                    class="rounded-xl border border-violet-200 bg-violet-50 p-5 transition hover:border-violet-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-800">Espace</p>
                    <p class="mt-2 text-lg font-semibold text-violet-950">Comité</p>
                    <p class="mt-1 text-sm text-violet-900">
                        Espace client et consultation des offres avec votre code personnel.
                    </p>
                </RouterLink>

                <RouterLink
                    v-if="canAccessAdmin"
                    :to="{ name: 'vente-encheres.admin.dashboard' }"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300 hover:shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Espace</p>
                    <p class="mt-2 text-lg font-semibold text-amber-950">Admin</p>
                    <p class="mt-1 text-sm text-amber-900">
                        Tous les espaces : client, comité et back-office.
                    </p>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import {
    canAccessVenteEncheresAdmin,
    canAccessVenteEncheresClient,
    canAccessVenteEncheresComite,
} from '../../config/vente-encheres-access';

const auth = useAuthStore();

const canAccessClient = computed(() => canAccessVenteEncheresClient(auth.baseUser ?? auth.user));
const canAccessComite = computed(() => canAccessVenteEncheresComite(auth.baseUser ?? auth.user));
const canAccessAdmin = computed(() => canAccessVenteEncheresAdmin(auth.baseUser ?? auth.user));
</script>
