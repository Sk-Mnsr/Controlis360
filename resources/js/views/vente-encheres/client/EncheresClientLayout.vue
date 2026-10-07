<template>
    <div class="ench-client-root">
        <EncheresClientHeader :menu-open="menuOpen" @toggle-menu="menuOpen = !menuOpen" />
        <EncheresMegaMenu :open="menuOpen" @close="menuOpen = false" />
        <main class="ench-client-main">
            <RouterView />
        </main>
        <EncheresAuthModal />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import EncheresClientHeader from '../../../components/vente-encheres/EncheresClientHeader.vue';
import EncheresMegaMenu from '../../../components/vente-encheres/EncheresMegaMenu.vue';
import EncheresAuthModal from '../../../components/vente-encheres/EncheresAuthModal.vue';
import { useEncheresClientAuthStore } from '../../../stores/encheresClientAuth';

const menuOpen = ref(false);
const clientAuth = useEncheresClientAuthStore();

onMounted(() => {
    clientAuth.restore();
});
</script>

<style scoped>
.ench-client-root {
    min-height: 100vh;
    background: #f5f7fa;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
}

.ench-client-main {
    flex: 1;
    width: 100%;
}

.ench-client-main :deep(.ench-detail),
.ench-client-main :deep(.ench-account),
.ench-client-main :deep(.ench-loading),
.ench-client-main :deep(.ench-empty) {
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    padding: 1.25rem 1rem 2.5rem;
}

@media (max-width: 680px) {
    .ench-client-main :deep(.ench-detail),
    .ench-client-main :deep(.ench-account),
    .ench-client-main :deep(.ench-loading),
    .ench-client-main :deep(.ench-empty) {
        padding: 0.85rem 0.75rem 2rem;
    }
}
</style>
