<script setup>
import { ref, watch } from 'vue';

import IconUtensils from '@/components/Icons/IconUtensils.vue';

defineOptions({
  name: 'CommonFoodImage',
});

const props = defineProps({
  src: {
    type: String,
    default: '',
  },
  alt: {
    type: String,
    default: '',
  },
  size: {
    type: Number,
    default: 96,
  },
  rounded: {
    type: Boolean,
    default: true,
  },
});

// Exibe o ícone quando a imagem não existe ou falha ao carregar
const falhou = ref(false);
watch(
  () => props.src,
  () => {
    falhou.value = false;
  },
);
</script>

<template>
  <span
    class="common-food-image"
    :class="{ 'is-rounded': rounded }"
    :style="{ width: `${size}px`, height: `${size}px` }"
  >
    <img
      v-if="src && !falhou"
      :src="src"
      :alt="alt"
      loading="lazy"
      @error="falhou = true"
    />
    <IconUtensils
      v-else
      class="common-food-image__fallback"
      aria-hidden="true"
    />
  </span>
</template>

<style scoped>
.common-food-image {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 14px;
  background: var(--color-primary-50);
}

.common-food-image.is-rounded {
  border-radius: 50%;
  box-shadow: 0 6px 16px rgba(17, 17, 24, 0.12);
}

.common-food-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.common-food-image__fallback {
  width: 38%;
  color: var(--color-primary-400);
}
</style>
