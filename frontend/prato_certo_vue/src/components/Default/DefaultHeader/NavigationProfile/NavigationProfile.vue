<script setup>
import CommonAvatar from '@/components/Common/CommonAvatar/CommonAvatar.vue';

import IconLogOut from '@/components/Icons/IconLogOut.vue';

defineOptions({
  name: 'NavigationProfile',
});

defineProps({
  name: {
    type: String,
    required: true,
  },
  role: {
    type: String,
    default: '',
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['logout']);
</script>

<template>
  <div
    class="navigation-profile"
    :class="{ 'is-compact': compact }"
  >
    <CommonAvatar
      :name="name"
      :size="compact ? 40 : 38"
      :title="`${name} · ${role}`"
    />

    <span
      v-if="!compact"
      class="navigation-profile__info"
    >
      <span class="navigation-profile__name">{{ name }}</span>
      <span
        v-if="role"
        class="navigation-profile__role"
      >
        {{ role }}
      </span>
    </span>

    <button
      type="button"
      class="navigation-profile__logout"
      aria-label="Sair do sistema"
      title="Sair"
      @click="emit('logout')"
    >
      <IconLogOut aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.navigation-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border-top: 1px solid var(--color-border-light);
}

.navigation-profile.is-compact {
  flex-direction: column;
  padding: 14px 8px 18px;
}

.navigation-profile__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.navigation-profile__name {
  overflow: hidden;
  color: var(--color-text-title);
  font-size: 0.86rem;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-profile__role {
  color: var(--color-text-tertiary);
  font-size: 0.75rem;
}

.navigation-profile__logout {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: var(--color-text-tertiary);
  transition:
    background-color 160ms ease,
    color 160ms ease;
}

.navigation-profile__logout svg {
  width: 18px;
}

@media (hover: hover) and (pointer: fine) {
  .navigation-profile__logout:hover {
    background: var(--color-error-50);
    color: var(--color-error-600);
  }
}
</style>
