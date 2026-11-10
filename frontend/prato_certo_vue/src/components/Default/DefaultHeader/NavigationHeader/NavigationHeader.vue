<script setup>
import { RouterLink } from 'vue-router';

defineOptions({
  name: 'NavigationHeader',
});

defineProps({
  title: {
    type: String,
    default: 'Prato Certo',
  },
  subtitle: {
    type: String,
    default: 'PDV',
  },
  compact: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div
    class="navigation-header"
    :class="{ 'is-compact': compact }"
  >
    <slot name="left" />

    <RouterLink
      to="/"
      class="navigation-header__brand"
      :aria-label="title"
    >
      <img
        src="/favicon.svg"
        alt=""
        class="navigation-header__logo"
        aria-hidden="true"
      />
      <span
        v-if="!compact"
        class="navigation-header__text"
      >
        <span class="navigation-header__title">{{ title }}</span>
        <span class="navigation-header__subtitle">{{ subtitle }}</span>
      </span>
    </RouterLink>
  </div>
</template>

<style scoped>
.navigation-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.navigation-header.is-compact {
  justify-content: center;
  padding: 20px 0 12px;
}

.navigation-header__brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.navigation-header__logo {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  box-shadow: var(--shadow-primary);
}

.navigation-header__text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.navigation-header__title {
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1rem;
  font-weight: 700;
}

.navigation-header__subtitle {
  color: var(--color-primary-600);
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
</style>
