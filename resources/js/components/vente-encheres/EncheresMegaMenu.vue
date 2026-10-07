<template>
    <div v-if="open" class="ench-mega-backdrop" @click.self="emit('close')">
        <div class="ench-mega-panel" role="dialog" aria-label="Enchère">
            <div class="ench-mega-sidebar">
                <p class="ench-mega-title">Enchère</p>
                <ul class="ench-mega-cats">
                    <li
                        v-for="cat in categories"
                        :key="cat.id"
                        class="ench-mega-cat"
                        :class="{ active: activeId === cat.id }"
                        @mouseenter="activeId = cat.id"
                        @click="activeId = cat.id"
                    >
                        <span class="ench-mega-cat-icon" aria-hidden="true">{{ cat.icon }}</span>
                        {{ cat.label }}
                    </li>
                </ul>
            </div>

            <div v-if="activeCategory" class="ench-mega-content">
                <h3 class="ench-mega-content-title">
                    {{ activeCategory.icon }} {{ activeCategory.label.toUpperCase() }}
                </h3>
                <div class="ench-mega-columns">
                    <div v-for="(col, idx) in activeCategory.columns" :key="idx" class="ench-mega-col">
                        <p class="ench-mega-col-title">{{ col.title }}</p>
                        <ul>
                            <li v-for="link in col.links" :key="link">
                                <button
                                    type="button"
                                    class="ench-mega-link"
                                    @click="selectCategory(activeCategory.id, link)"
                                >
                                    {{ link }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useEncheresAdminStore } from '../../stores/encheresAdmin';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const router = useRouter();
const store = useEncheresAdminStore();
const categories = computed(() => store.activeCategories);
const activeId = ref(categories.value[0]?.id ?? null);

const activeCategory = computed(() => categories.value.find((c) => c.id === activeId.value) ?? null);

watch(() => props.open, (isOpen) => {
    if (isOpen && (!activeId.value || !categories.value.some((c) => c.id === activeId.value))) {
        activeId.value = categories.value[0]?.id ?? null;
    }
});

watch(categories, (list) => {
    if (!list.some((c) => c.id === activeId.value)) {
        activeId.value = list[0]?.id ?? null;
    }
});

function selectCategory(categoryId, sub) {
    emit('close');
    router.push({
        name: 'vente-encheres.client.list',
        query: { category_id: categoryId, subcategory: sub },
    });
}
</script>

<style scoped>
.ench-mega-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 4.5rem 0 0 0;
}

.ench-mega-panel {
    display: flex;
    width: min(920px, 100%);
    max-height: min(70vh, 520px);
    margin-left: 0;
    background: #fff;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
    border-radius: 0 0.75rem 0.75rem 0;
    overflow: hidden;
}

.ench-mega-sidebar {
    width: 240px;
    flex-shrink: 0;
    background: #0f172a;
    color: #f8fafc;
    padding: 1rem 0;
    overflow-y: auto;
}

.ench-mega-title {
    margin: 0 1rem 0.75rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #94a3b8;
}

.ench-mega-cats {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ench-mega-cat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border-left: 3px solid transparent;
}

.ench-mega-cat:hover,
.ench-mega-cat.active {
    background: rgba(37, 99, 235, 0.15);
    border-left-color: #2563eb;
}

.ench-mega-cat-icon {
    width: 1.25rem;
    text-align: center;
}

.ench-mega-content {
    flex: 1;
    padding: 1.25rem 1.5rem;
    overflow-y: auto;
    background: #f8fafc;
}

.ench-mega-content-title {
    margin: 0 0 1rem;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.04em;
}

.ench-mega-columns {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1.25rem;
}

.ench-mega-col-title {
    margin: 0 0 0.5rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}

.ench-mega-col ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ench-mega-link {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: none;
    padding: 0.35rem 0;
    font-size: 0.875rem;
    color: #334155;
    cursor: pointer;
}

.ench-mega-link:hover {
    color: #2563eb;
    font-weight: 600;
}

@media (max-width: 820px) {
    .ench-mega-backdrop {
        padding-top: 9.5rem;
    }

    .ench-mega-panel {
        flex-direction: column;
        max-height: calc(100vh - 10rem);
        margin: 0;
        border-radius: 0;
        width: 100%;
    }

    .ench-mega-sidebar {
        width: 100%;
        max-height: 40vh;
    }
}
</style>
