<template>
    <div v-if="savedPlans.length" class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
        <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            Réponses aux actions
        </h4>
        <div class="space-y-4">
            <div
                v-for="plan in savedPlans"
                :key="plan.id"
                class="rounded-lg border border-slate-200 bg-white p-4"
            >
                <p class="mb-2 text-xs font-medium text-slate-600">
                    <span class="text-slate-400">Ligne {{ plan.line_number }}</span>
                    — {{ plan.action_plan }}
                </p>
                <MissionRecoActionPlanComments
                    :plan-id="plan.id"
                    :comments="plan.comments ?? []"
                    :can-comment="canComment"
                    @saved="$emit('updated')"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import MissionRecoActionPlanComments from './MissionRecoActionPlanComments.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    canComment: { type: Boolean, default: true },
});

defineEmits(['updated']);

const savedPlans = computed(() => (props.plans ?? []).filter((plan) => plan.id));
</script>
