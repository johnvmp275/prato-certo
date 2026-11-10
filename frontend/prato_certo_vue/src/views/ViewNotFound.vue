<script setup>
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

defineOptions({
  name: 'ViewNotFound',
});
</script>

<template>
  <div class="view-not-found">
    <p class="view-not-found__code">404</p>
    <h1 class="view-not-found__title">Página não encontrada</h1>
    <p class="view-not-found__text">O endereço acessado não existe ou foi removido.</p>
    <RouterLink to="/">
      <CommonButton>Voltar ao início</CommonButton>
    </RouterLink>
  </div>
</template>

<style scoped>
.view-not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
}

.view-not-found__code {
  color: var(--color-primary-500);
  font-family: var(--font-title);
  font-size: 4rem;
  font-weight: 700;
  line-height: 1;
}

.view-not-found__title {
  font-size: 1.4rem;
}

.view-not-found__text {
  margin-bottom: 12px;
  color: var(--color-text-secondary);
}
</style>
