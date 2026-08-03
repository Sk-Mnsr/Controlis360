<template>
    <RouterView />
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();

onMounted(() => {
    if (auth.token) {
        auth.fetchUser();
    }
});

watch(
    () => auth.token,
    (token) => {
        if (token) {
            auth.startIdleWatch();
        } else {
            auth.stopIdleWatch();
        }
    },
    { immediate: true },
);
</script>
