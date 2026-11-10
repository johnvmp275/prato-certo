<script setup>
defineOptions({
  name: 'CommonPageHeader',
});

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <div class="common-page-header">
    <div class="common-page-header__text">
      <h1 class="common-page-header__title">{{ title }}</h1>
      <p
        v-if="subtitle"
        class="common-page-header__subtitle"
      >
        {{ subtitle }}
      </p>
    </div>

    <div
      v-if="$slots.actions"
      class="common-page-header__actions"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
.common-page-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
}

.common-page-header__title {
  font-size: 1.45rem;
  font-weight: 600;
}

.common-page-header__subtitle {
  margin-top: 4px;
  color: var(--color-text-secondary);
  font-size: 0.9rem;
}

.common-page-header__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

@media (min-width: 768px) {
  .common-page-header__title {
    font-size: 1.6rem;
  }
}
</style>
