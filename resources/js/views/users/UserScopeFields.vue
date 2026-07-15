<template>
    <div v-if="visible" class="md:col-span-2 grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">
                Environnements
                <span class="text-red-600">*</span>
            </label>
            <div class="max-h-44 overflow-y-auto rounded-lg border border-slate-300 p-3">
                <p v-if="!availableEnvironments.length" class="text-sm text-slate-500">
                    Aucun environnement disponible
                </p>
                <label
                    v-for="environment in availableEnvironments"
                    :key="environment.id"
                    class="flex cursor-pointer items-center gap-2 py-1 text-sm"
                >
                    <input
                        type="checkbox"
                        class="rounded border-slate-300"
                        :value="environment.id"
                        :checked="environmentIds.includes(environment.id)"
                        :disabled="isEnvironmentLocked(environment.id)"
                        @change="toggleEnvironment(environment.id, $event.target.checked)"
                    />
                    <span>{{ environment.name }}</span>
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Entités</label>
            <div class="max-h-44 overflow-y-auto rounded-lg border border-slate-300 p-3">
                <p v-if="!environmentIds.length" class="text-sm text-slate-500">
                    Sélectionnez d'abord au moins un environnement
                </p>
                <p v-else-if="!availableEntities.length" class="text-sm text-slate-500">
                    Aucune entité disponible
                </p>
                <label
                    v-for="entity in availableEntities"
                    :key="entity.id"
                    class="flex cursor-pointer items-center gap-2 py-1 text-sm"
                >
                    <input
                        type="checkbox"
                        class="rounded border-slate-300"
                        :value="entity.id"
                        :checked="entityIds.includes(entity.id)"
                        @change="toggleEntity(entity.id, $event.target.checked)"
                    />
                    <span>{{ entityLabel(entity) }}</span>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    visible: {
        type: Boolean,
        default: true,
    },
    environments: {
        type: Array,
        default: () => [],
    },
    entities: {
        type: Array,
        default: () => [],
    },
    environmentIds: {
        type: Array,
        default: () => [],
    },
    entityIds: {
        type: Array,
        default: () => [],
    },
    lockedEnvironmentIds: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:environmentIds', 'update:entityIds']);

const availableEnvironments = computed(() => props.environments);

const availableEntities = computed(() => {
    if (!props.environmentIds.length) {
        return [];
    }

    return props.entities.filter((entity) => props.environmentIds.includes(entity.environment_id));
});

function isEnvironmentLocked(environmentId) {
    return props.lockedEnvironmentIds.includes(environmentId);
}

function toggleEnvironment(environmentId, checked) {
    const nextEnvironmentIds = checked
        ? [...new Set([...props.environmentIds, environmentId])]
        : props.environmentIds.filter((id) => id !== environmentId);

    const allowedEntityIds = props.entities
        .filter((entity) => nextEnvironmentIds.includes(entity.environment_id))
        .map((entity) => entity.id);

    emit('update:environmentIds', nextEnvironmentIds);
    emit('update:entityIds', props.entityIds.filter((id) => allowedEntityIds.includes(id)));
}

function toggleEntity(entityId, checked) {
    const nextEntityIds = checked
        ? [...new Set([...props.entityIds, entityId])]
        : props.entityIds.filter((id) => id !== entityId);

    emit('update:entityIds', nextEntityIds);
}

function entityLabel(entity) {
    const environment = props.environments.find((item) => item.id === entity.environment_id);
    return environment ? `${entity.name} (${environment.name})` : entity.name;
}
</script>
