<script setup>
import { watch, ref } from 'vue';

import { useRoute } from 'vue-router';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';

import IconMenu from '@/components/Icons/IconMenu.vue';

import NavigationInner from './NavigationInner/NavigationInner.vue';
import NavigationHeader from './NavigationHeader/NavigationHeader.vue';

import { useNavigation } from '@/stores/useNavigation';

import { useDevice } from '@/composables/useDevice.js';

defineOptions({
  name: 'DefaultHeader',
});

const { isDesktop } = useDevice();

const navigation = useNavigation();

const route = useRoute();
const sheetRef = ref(null);

watch(
  () => route.fullPath,
  () => {
    sheetRef.value?.close();
  },
);
</script>

<template>
  <template v-if="!isDesktop">
    <NavigationHeader class="default-header__mobile">
      <template #left>
        <button
          type="button"
          class="default-header__trigger"
          aria-label="Abrir navegação"
          :aria-expanded="navigation.isOpen"
          @click="navigation.toggleNavigation"
        >
          <IconMenu aria-hidden="true" />
        </button>
      </template>
    </NavigationHeader>

    <CommonSheet
      v-if="navigation.isOpen"
      ref="sheetRef"
      position="left"
      :size="300"
      @close="navigation.closeNavigation"
    >
      <template #content>
        <div class="default-header__panel">
          <NavigationInner />
        </div>
      </template>
    </CommonSheet>
  </template>

  <aside
    v-else
    class="default-header__sidebar"
  >
    <NavigationInner compact />
  </aside>
</template>

<style scoped>
.default-header__trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--color-neutral-100);
  color: var(--color-icon-primary);
  cursor: pointer;
}

.default-header__trigger svg {
  width: 20px;
}

.default-header__sidebar {
  position: sticky;
  top: 0;
  width: 96px;
  height: 100vh;
  height: 100dvh;
  border-right: 1px solid var(--color-border-light);
  background: var(--color-sidebar-background);
}

.default-header__panel {
  height: 100%;
  background: var(--color-sidebar-background);
}

.default-header__mobile {
  position: sticky;
  top: 0;
  z-index: 10;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border-light);
  background: var(--color-sidebar-background);
}
</style>
