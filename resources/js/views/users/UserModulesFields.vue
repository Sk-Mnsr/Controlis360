<template>
    <div class="space-y-3">
        <div v-if="!hideHeading">
            <label class="mb-1 block text-sm font-medium">Modules et profils</label>
            <p class="text-xs text-slate-500">
                Activez un module et choisissez le profil (et le rôle) pour ce module.
            </p>
        </div>
        <p v-else class="text-xs text-slate-500">
            Activez un module et choisissez le profil (et le rôle) pour ce module.
        </p>

        <div
            v-for="assignment in modelValue"
            :key="assignment.slug"
            class="rounded-xl border border-slate-200 bg-slate-50 p-4"
        >
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <input
                    type="checkbox"
                    class="rounded border-slate-300"
                    :checked="assignment.enabled"
                    :disabled="disabled"
                    @change="updateAssignment(assignment.slug, { enabled: $event.target.checked })"
                >
                {{ moduleName(assignment.slug) }}
            </label>

            <div v-if="assignment.enabled" class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Profil du module</label>
                    <select
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        :value="assignment.profile"
                        :disabled="disabled"
                        @change="updateAssignment(assignment.slug, { profile: $event.target.value })"
                    >
                        <option
                            v-for="option in profileOptions(assignment.slug)"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div v-if="isGouvernanceProfile(assignment.profile)">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Rôle Gouvernance IT</label>
                    <input
                        type="text"
                        readonly
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold tracking-wide text-slate-700"
                        :value="gouvernanceRoleLabel(assignment.profile)"
                    />
                </div>

                <div v-if="assignment.profile === 'controle'">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Rôle contrôle</label>
                    <select
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        :value="assignment.controle_role"
                        :disabled="disabled"
                        @change="updateAssignment(assignment.slug, { controle_role: $event.target.value })"
                    >
                        <option value="agent_controle_interne">Agent du contrôle interne</option>
                        <option value="responsable_controle_permanent">Responsable Contrôle permanent &amp; risques opérationnels</option>
                    </select>
                </div>

                <div v-if="assignment.profile === 'audit'">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Rôle audit</label>
                    <select
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        :value="assignment.audit_role"
                        :disabled="disabled"
                        @change="updateAssignment(assignment.slug, { audit_role: $event.target.value })"
                    >
                        <option value="agent_audit">Agent audit</option>
                        <option value="responsable_audit">Responsable audit</option>
                    </select>
                </div>

                <div v-if="assignment.profile === 'metier'">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Rôle métier</label>
                    <select
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        :value="assignment.metier_role"
                        :disabled="disabled"
                        @change="updateAssignment(assignment.slug, { metier_role: $event.target.value })"
                    >
                        <option value="responsable_entite">Responsable entité</option>
                        <option value="groupe">Groupe</option>
                        <option value="visiteur">Visiteur</option>
                        <option value="agent">Agent</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { modules } from '../../config/modules';
import {
    GOUVERNANCE_IT_PROFILES,
    GOUVERNANCE_IT_ROLE_BY_PROFILE,
    MODULE_PROFILE_OPTIONS,
} from '../../config/module-access';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    hideHeading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

function moduleName(slug) {
    return modules.find((module) => module.slug === slug)?.name ?? slug;
}

function profileOptions(slug) {
    return MODULE_PROFILE_OPTIONS[slug] ?? [];
}

function isGouvernanceProfile(profile) {
    return GOUVERNANCE_IT_PROFILES.includes(profile);
}

function gouvernanceRoleLabel(profile) {
    return GOUVERNANCE_IT_ROLE_BY_PROFILE[profile] ?? '';
}

function updateAssignment(slug, patch) {
    emit(
        'update:modelValue',
        props.modelValue.map((item) => (item.slug === slug ? { ...item, ...patch } : item)),
    );
}
</script>
