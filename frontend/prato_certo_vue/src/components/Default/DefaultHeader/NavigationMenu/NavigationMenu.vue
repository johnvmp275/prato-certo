<script setup>
import NavigationMenuItem from './NavigationMenuItem/NavigationMenuItem.vue';

defineOptions({
  name: 'NavigationMenu',
});

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  compact: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <nav
    class="navigation-menu"
    :class="{ 'is-compact': compact }"
    aria-label="Navegação principal"
  >
    <ul class="navigation-menu__list">
      <li
        v-for="item in items"
        :key="item.key ?? item.label"
      >
        <NavigationMenuItem
          :icon="item.icon"
          :label="item.label"
          :link="item.link ?? ''"
          :badge="item.badge ?? null"
          :externo="Boolean(item.externo)"
          :compact="compact"
        />
      </li>
    </ul>
  </nav>
</template>

<style scoped>
.navigation-menu {
  flex: 1;
  overflow-y: auto;
  padding: 4px 12px;
  scrollbar-width: none;
}

.navigation-menu::-webkit-scrollbar {
  display: none;
}

.navigation-menu.is-compact {
  padding: 4px 10px;
}

.navigation-menu__list {
  display: grid;
  gap: 4px;
}
</style>
