<template>
    <div class="placeholder">
        <h2>Contrats IT</h2>
        <p>Section en préparation — suivi des contrats IT.</p>
        <RouterLink :to="{ name: 'cartographie-applications.home' }" class="back">
            ← Retour aux Services IT
        </RouterLink>
    </div>
</template>

<style scoped>
.placeholder {
    padding: 1.5rem 1.25rem;
}

h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #0f172a;
}

p {
    margin: 0.5rem 0 1rem;
    color: #64748b;
}

.back {
    color: #0f4c81;
    font-weight: 600;
    text-decoration: none;
}
</style>
