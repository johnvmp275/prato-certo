<script setup>
import { computed } from 'vue';

import { RouterLink, useRoute } from 'vue-router';

defineOptions({
  name: 'NavigationMenuItem',
});

const props = defineProps({
  icon: {
    type: [Object, Function],
    default: null,
  },
  label: {
    type: String,
    required: true,
  },
  link: {
    type: String,
    default: '',
  },
  badge: {
    type: [String, Number],
    default: null,
  },
  externo: {
    type: Boolean,
    default: false,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const route = useRoute();

// A home ("/") só fica ativa na própria rota; as demais também nas sub-rotas
const isActive = computed(() => {
  if (props.externo) return false;
  if (props.link === '/') return route.path === '/';
  return route.path.startsWith(props.link);
});
</script>

<template>
  <component
    :is="externo ? 'a' : RouterLink"
    :to="externo ? undefined : link"
    :href="externo ? link : undefined"
    :target="externo ? '_blank' : undefined"
    :rel="externo ? 'noopener' : undefined"
    class="navigation-menu-item"
    :class="{ 'is-active': isActive, 'is-compact': compact }"
    :aria-current="isActive ? 'page' : undefined"
  >
    <span
      v-if="icon"
      class="navigation-menu-item__icon"
      aria-hidden="true"
    >
      <component :is="icon" />
      <span
        v-if="badge !== null && compact"
        class="navigation-menu-item__dot"
      >
        {{ badge }}
      </span>
    </span>

    <span class="navigation-menu-item__label">{{ label }}</span>

    <span
      v-if="badge !== null && !compact"
      class="navigation-menu-item__badge"
    >
      {{ badge }}
    </span>
  </component>
</template>

<style scoped>
.navigation-menu-item {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 46px;
  padding: 0 14px;
  border-radius: 12px;
  background: var(--color-sidebar-item);
  color: var(--color-text-secondary);
  font-size: 0.88rem;
  font-weight: 500;
  transition:
    background-color 160ms ease,
    color 160ms ease;
}

.navigation-menu-item__icon {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  color: var(--color-icon-muted);
}

.navigation-menu-item__label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-menu-item__badge,
.navigation-menu-item__dot {
  min-width: 20px;
  padding: 1px 6px;
  border-radius: 999px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
  line-height: 1.5;
  text-align: center;
}

.navigation-menu-item__dot {
  position: absolute;
  top: -8px;
  right: -12px;
  min-width: 18px;
  padding: 0 4px;
  border: 2px solid #ffffff;
}

/* BARRA COMPACTA (DESKTOP): ÍCONE EM CIMA E TEXTO EMBAIXO */
.navigation-menu-item.is-compact {
  flex-direction: column;
  justify-content: center;
  gap: 4px;
  min-height: 62px;
  padding: 8px 4px;
  font-size: 0.68rem;
  text-align: center;
}

.navigation-menu-item.is-compact .navigation-menu-item__label {
  flex: none;
  width: 100%;
}

/* ATIVO: FUNDO LARANJA CLARO + BARRA LATERAL */
.navigation-menu-item.is-active {
  background: var(--color-sidebar-item-active);
  color: var(--color-primary-600);
  font-weight: 600;
}

.navigation-menu-item.is-active .navigation-menu-item__icon {
  color: var(--color-primary-500);
}

.navigation-menu-item.is-active::before {
  content: '';
  position: absolute;
  top: 12px;
  bottom: 12px;
  left: -12px;
  width: 4px;
  border-radius: 0 4px 4px 0;
  background: var(--color-primary-500);
}

.navigation-menu-item.is-compact.is-active::before {
  left: -10px;
}

@media (hover: hover) and (pointer: fine) {
  .navigation-menu-item:not(.is-active):hover {
    background: var(--color-sidebar-item-hover);
    color: var(--color-text-title);
  }
}
</style>
