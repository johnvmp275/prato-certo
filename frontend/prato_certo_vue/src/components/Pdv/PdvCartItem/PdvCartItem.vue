<script setup>
import { ref } from 'vue';

import CommonQuantity from '@/components/Common/CommonQuantity/CommonQuantity.vue';

import IconNote from '@/components/Icons/IconNote.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';

import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'PdvCartItem',
});

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['quantidade', 'observacao', 'remover']);

const editandoObservacao = ref(Boolean(props.item.observacao));
</script>

<template>
  <li class="pdv-cart-item">
    <span class="pdv-cart-item__index">{{ index + 1 }}</span>

    <div class="pdv-cart-item__body">
      <div class="pdv-cart-item__row">
        <span class="pdv-cart-item__name">{{ item.nome }}</span>
        <span class="pdv-cart-item__total tabular">{{ formatarMoeda(item.preco * item.quantidade) }}</span>
      </div>

      <div class="pdv-cart-item__row">
        <span class="pdv-cart-item__unit tabular">{{ formatarMoeda(item.preco) }} un.</span>

        <div
          v-if="!readonly"
          class="pdv-cart-item__actions"
        >
          <button
            type="button"
            class="pdv-cart-item__icon"
            :class="{ 'is-active': editandoObservacao }"
            aria-label="Observação do item"
            title="Observação"
            @click="editandoObservacao = !editandoObservacao"
          >
            <IconNote aria-hidden="true" />
          </button>
          <button
            type="button"
            class="pdv-cart-item__icon pdv-cart-item__icon--danger"
            aria-label="Remover item"
            title="Remover"
            @click="emit('remover', index)"
          >
            <IconTrash aria-hidden="true" />
          </button>
          <CommonQuantity
            size="sm"
            :min="1"
            :model-value="item.quantidade"
            @update:model-value="emit('quantidade', index, $event)"
          />
        </div>
        <span
          v-else
          class="pdv-cart-item__unit"
        >
          x{{ item.quantidade }}
        </span>
      </div>

      <input
        v-if="editandoObservacao && !readonly"
        type="text"
        class="pdv-cart-item__note"
        placeholder="Ex: sem cebola, ponto da carne..."
        maxlength="150"
        :value="item.observacao"
        aria-label="Observação do item"
        @change="emit('observacao', index, $event.target.value)"
      />
      <p
        v-else-if="item.observacao"
        class="pdv-cart-item__note-text"
      >
        “{{ item.observacao }}”
      </p>
    </div>
  </li>
</template>

<style scoped>
.pdv-cart-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px dashed var(--color-border-default);
}

.pdv-cart-item:last-child {
  border-bottom: none;
}

.pdv-cart-item__index {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 8px;
  background: var(--color-neutral-100);
  color: var(--color-text-secondary);
  font-size: 0.72rem;
  font-weight: 700;
}

.pdv-cart-item__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.pdv-cart-item__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.pdv-cart-item__name {
  color: var(--color-text-title);
  font-size: 0.88rem;
  font-weight: 600;
}

.pdv-cart-item__total {
  flex-shrink: 0;
  color: var(--color-text-title);
  font-size: 0.88rem;
  font-weight: 700;
}

.pdv-cart-item__unit {
  color: var(--color-text-tertiary);
  font-size: 0.76rem;
}

.pdv-cart-item__actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.pdv-cart-item__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  color: var(--color-text-tertiary);
}

.pdv-cart-item__icon svg {
  width: 15px;
}

.pdv-cart-item__icon.is-active {
  background: var(--color-primary-50);
  color: var(--color-primary-600);
}

.pdv-cart-item__note {
  width: 100%;
  padding: 7px 10px;
  border: 1px solid var(--color-input-border);
  border-radius: 8px;
  font-size: 0.8rem;
}

.pdv-cart-item__note:focus {
  border-color: var(--color-input-border-focus);
}

.pdv-cart-item__note-text {
  color: var(--color-primary-700);
  font-size: 0.78rem;
  font-style: italic;
}

@media (hover: hover) and (pointer: fine) {
  .pdv-cart-item__icon:hover {
    background: var(--color-neutral-100);
    color: var(--color-text-title);
  }
  .pdv-cart-item__icon--danger:hover {
    background: var(--color-error-50);
    color: var(--color-error-600);
  }
}
</style>
