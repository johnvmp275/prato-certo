<script setup>
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';

import IconPlus from '@/components/Icons/IconPlus.vue';

import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'CardapioProdutoCard',
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
  <article
    class="cardapio-produto-card"
    :class="{ 'is-disabled': !produto.disponivel }"
  >
    <div class="cardapio-produto-card__info">
      <h3 class="cardapio-produto-card__name">{{ produto.nome }}</h3>
      <p
        v-if="produto.descricao"
        class="cardapio-produto-card__description"
      >
        {{ produto.descricao }}
      </p>
      <p class="cardapio-produto-card__price tabular">{{ formatarMoeda(produto.preco) }}</p>
    </div>

    <div class="cardapio-produto-card__media">
      <CommonFoodImage
        :src="produto.imagem"
        :alt="produto.nome"
        :size="96"
        :rounded="false"
      />
      <button
        v-if="produto.disponivel"
        type="button"
        class="cardapio-produto-card__add"
        :aria-label="`Adicionar ${produto.nome} ao carrinho`"
        @click="emit('add', produto)"
      >
        <span
          v-if="quantidade"
          class="tabular"
        >
          {{ quantidade }}
        </span>
        <IconPlus
          v-else
          aria-hidden="true"
        />
      </button>
      <span
        v-else
        class="cardapio-produto-card__out"
      >
        Esgotado
      </span>
    </div>
  </article>
</template>

<style scoped>
.cardapio-produto-card {
  display: flex;
  gap: 14px;
  padding: 14px;
  border: 1px solid var(--color-border-light);
  border-radius: 18px;
  background: var(--color-surface);
}

.cardapio-produto-card.is-disabled {
  opacity: 0.6;
}

.cardapio-produto-card__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.cardapio-produto-card__name {
  font-family: var(--font-body);
  font-size: 0.95rem;
  font-weight: 600;
}

.cardapio-produto-card__description {
  display: -webkit-box;
  overflow: hidden;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  line-height: 1.45;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.cardapio-produto-card__price {
  margin-top: auto;
  color: var(--color-text-title);
  font-weight: 700;
}

.cardapio-produto-card__media {
  position: relative;
  flex-shrink: 0;
}

.cardapio-produto-card__media :deep(.common-food-image) {
  border-radius: 14px;
}

.cardapio-produto-card__add {
  position: absolute;
  right: -6px;
  bottom: -6px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 3px solid var(--color-surface);
  border-radius: 12px;
  background: var(--color-primary-500);
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 700;
  box-shadow: var(--shadow-primary);
}

.cardapio-produto-card__add svg {
  width: 17px;
}

.cardapio-produto-card__out {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  padding: 3px 0;
  border-radius: 0 0 14px 14px;
  background: rgba(17, 17, 24, 0.7);
  color: #ffffff;
  font-size: 0.7rem;
  font-weight: 600;
  text-align: center;
}
</style>
