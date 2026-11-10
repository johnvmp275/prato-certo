<script setup>
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';

import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'PdvProductCard',
});

defineProps({
  produto: {
    type: Object,
    required: true,
  },
  quantidade: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['add']);
</script>

<template>
  <button
    type="button"
    class="pdv-product-card"
    :class="{ 'is-selected': quantidade > 0, 'is-disabled': !produto.disponivel }"
    :disabled="!produto.disponivel"
    :aria-label="`Adicionar ${produto.nome}, ${formatarMoeda(produto.preco)}`"
    @click="emit('add', produto)"
  >
    <span
      v-if="quantidade > 0"
      class="pdv-product-card__qty"
    >
      {{ quantidade }}
    </span>

    <CommonFoodImage
      :src="produto.imagem"
      :alt="produto.nome"
      :size="92"
    />

    <span class="pdv-product-card__name">{{ produto.nome }}</span>

    <span class="pdv-product-card__footer">
      <span class="pdv-product-card__price tabular">{{ formatarMoeda(produto.preco) }}</span>
      <span
        v-if="produto.disponivel"
        class="pdv-product-card__add"
        aria-hidden="true"
      >
        <IconPlus />
      </span>
      <span
        v-else
        class="pdv-product-card__out"
      >
        Esgotado
      </span>
    </span>

    <span
      v-if="produto.controlaEstoque && produto.disponivel"
      class="pdv-product-card__stock"
    >
      {{ produto.estoque }} em estoque
    </span>
  </button>
</template>

<style scoped>
.pdv-product-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 18px 14px 14px;
  border: 1.5px solid var(--color-border-light);
  border-radius: 18px;
  background: var(--color-surface);
  text-align: center;
  cursor: pointer;
  transition:
    border-color 160ms ease,
    box-shadow 160ms ease;
}

.pdv-product-card.is-selected {
  border-color: var(--color-primary-400);
  box-shadow: 0 0 0 3px var(--color-primary-50);
}

.pdv-product-card.is-disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.pdv-product-card__qty {
  position: absolute;
  top: 10px;
  right: 10px;
  min-width: 24px;
  padding: 2px 7px;
  border-radius: 999px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
}

.pdv-product-card__name {
  display: -webkit-box;
  min-height: 2.6em;
  overflow: hidden;
  color: var(--color-text-title);
  font-size: 0.86rem;
  font-weight: 600;
  line-height: 1.3;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.pdv-product-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
}

.pdv-product-card__price {
  color: var(--color-primary-600);
  font-weight: 700;
}

.pdv-product-card__add {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 10px;
  background: var(--color-primary-500);
  color: #ffffff;
}

.pdv-product-card__add svg {
  width: 16px;
}

.pdv-product-card__out {
  color: var(--color-error-600);
  font-size: 0.72rem;
  font-weight: 600;
}

.pdv-product-card__stock {
  color: var(--color-text-tertiary);
  font-size: 0.7rem;
}

@media (hover: hover) and (pointer: fine) {
  .pdv-product-card:not(:disabled):hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-md);
  }
}
</style>
