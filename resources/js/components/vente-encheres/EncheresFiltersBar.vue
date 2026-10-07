<template>
    <form class="ench-filters" @submit.prevent="emit('apply', { ...local })">
        <div class="ench-filters-grid">
            <label>
                <span>Catégorie</span>
                <select v-model="local.category_id" class="ench-filters-input">
                    <option value="">Toutes</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.label }}</option>
                </select>
            </label>
            <label>
                <span>Région</span>
                <select v-model="local.region" class="ench-filters-input">
                    <option value="">Toutes</option>
                    <option v-for="r in regions" :key="r" :value="r">{{ r }}</option>
                </select>
            </label>
            <label>
                <span>Prix min (FCFA)</span>
                <input v-model.number="local.min_price" type="number" min="0" class="ench-filters-input" />
            </label>
            <label>
                <span>Prix max (FCFA)</span>
                <input v-model.number="local.max_price" type="number" min="0" class="ench-filters-input" />
            </label>
            <label>
                <span>Statut</span>
                <select v-model="local.status" class="ench-filters-input">
                    <option value="">Tous</option>
                    <option value="live">En cours</option>
                    <option value="ended">Terminées</option>
                </select>
            </label>
        </div>
        <div class="ench-filters-actions">
            <button type="submit" class="ench-filters-btn ench-filters-btn--primary">Filtrer</button>
            <button type="button" class="ench-filters-btn" @click="reset">Réinitialiser</button>
        </div>
    </form>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { useEncheresAdminStore } from '../../stores/encheresAdmin';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['apply', 'update:modelValue']);

const store = useEncheresAdminStore();
const categories = computed(() => store.activeCategories);
const regions = computed(() => store.regions);

const local = reactive({
    category_id: '',
    region: '',
    min_price: '',
    max_price: '',
    status: '',
});

watch(
    () => props.modelValue,
    (v) => {
        Object.assign(local, {
            category_id: v?.category_id ?? '',
            region: v?.region ?? '',
            min_price: v?.min_price ?? '',
            max_price: v?.max_price ?? '',
            status: v?.status ?? '',
        });
    },
    { immediate: true, deep: true },
);

function reset() {
    Object.assign(local, {
        category_id: '',
        region: '',
        min_price: '',
        max_price: '',
        status: '',
    });
    emit('apply', { ...local });
}
</script>

<style scoped>
.ench-filters {
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem;
    margin-bottom: 1.25rem;
}

.ench-filters-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
}

.ench-filters-grid label {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
}

.ench-filters-input {
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    padding: 0.45rem 0.6rem;
    font-size: 0.875rem;
}

.ench-filters-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.85rem;
}

.ench-filters-btn {
    border-radius: 0.45rem;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 700;
    cursor: pointer;
}

.ench-filters-btn--primary {
    border-color: #2563eb;
    background: #2563eb;
    color: #fff;
}

@media (max-width: 680px) {
    .ench-filters-grid {
        grid-template-columns: 1fr;
    }

    .ench-filters-actions {
        flex-direction: column;
    }

    .ench-filters-btn {
        width: 100%;
        min-height: 2.75rem;
    }
}
</style>
