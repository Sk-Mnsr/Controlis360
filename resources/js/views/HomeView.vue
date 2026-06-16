<template>
    <div class="home-page" :style="pageBackgroundStyle">
        <p v-if="cartographie.statusMessage" class="home-status">
            {{ cartographie.statusMessage }}
        </p>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useCartographieStore } from '../stores/cartographie';

const route = useRoute();
const cartographie = useCartographieStore();
const riskImageUrl = '/risk.png';

const pageBackgroundStyle = computed(() => ({
    backgroundImage: `linear-gradient(90deg, rgba(15,23,42,0.08) 0%, rgba(255,255,255,0.05) 40%, rgba(255,255,255,0.18) 100%), url('${riskImageUrl}')`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
    backgroundRepeat: 'no-repeat',
}));

watch(
    () => route.name,
    (name) => {
        if (name === 'cartographie.home') {
            return;
        }

        cartographie.statusMessage = '';
    },
);
</script>

<style scoped>
.home-page {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 100vh;
    align-items: flex-end;
    justify-content: center;
    padding: 1.5rem;
}

.home-status {
    position: relative;
    z-index: 1;
    max-width: 36rem;
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.72);
    padding: 0.85rem 1.1rem;
    text-align: center;
    font-size: 0.8125rem;
    color: #f8fafc;
    backdrop-filter: blur(6px);
}
</style>
