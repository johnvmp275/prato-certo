<script setup>
import { computed } from 'vue';

import IconUsers from '@/components/Icons/IconUsers.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';

import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'MesaCard',
});

const props = defineProps({
  mesa: {
    type: Object,
    required: true,
  },
  editavel: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select', 'edit']);

const ROTULOS = {
  livre: 'Livre',
  ocupada: 'Ocupada',
  em_espera: 'Em espera',
};

// Cadeiras distribuídas ao redor da mesa (metade em cima, metade embaixo)
const cadeiras = computed(() => {
  const total = Math.min(props.mesa.capacidade, 10);
  const cima = Math.ceil(total / 2);
  return { cima, baixo: total - cima };
});
</script>

<template>
  <div
    class="mesa-card"
    :class="[`mesa-card--${mesa.estado}`, { 'is-inativa': mesa.status === 'inativo' }]"
  >
    <button
      type="button"
      class="mesa-card__button"
      :aria-label="`Mesa ${mesa.numero}, ${ROTULOS[mesa.estado]}, ${mesa.capacidade} lugares`"
      :disabled="mesa.status === 'inativo'"
      @click="emit('select', mesa)"
    >
      <span
        class="mesa-card__chairs"
        aria-hidden="true"
      >
        <span
          v-for="n in cadeiras.cima"
          :key="`c${n}`"
        />
      </span>

      <span class="mesa-card__table">
        <span class="mesa-card__number">M{{ String(mesa.numero).padStart(2, '0') }}</span>
        <span
          v-if="mesa.pedido"
          class="mesa-card__code"
        >
          #{{ mesa.pedido.codigo }}
        </span>
        <span
          v-else
          class="mesa-card__capacity"
        >
          <IconUsers aria-hidden="true" />
          {{ mesa.capacidade }}
        </span>
      </span>

      <span
        class="mesa-card__chairs"
        aria-hidden="true"
      >
        <span
          v-for="n in cadeiras.baixo"
          :key="`b${n}`"
        />
      </span>

      <span class="mesa-card__info">
        <span class="mesa-card__status">{{ mesa.status === 'inativo' ? 'Inativa' : ROTULOS[mesa.estado] }}</span>
        <span
          v-if="mesa.pedido"
          class="mesa-card__total tabular"
        >
          {{ mesa.pedido.numPessoas }}p · {{ formatarMoeda(mesa.pedido.total) }}
        </span>
      </span>
    </button>

    <button
      v-if="editavel"
      type="button"
      class="mesa-card__edit"
      :aria-label="`Editar mesa ${mesa.numero}`"
      @click="emit('edit', mesa)"
    >
      <IconPencil aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.mesa-card {
  --mesa-cor: var(--color-status-livre);
  --mesa-fundo: var(--color-success-50);

  position: relative;
}

.mesa-card--ocupada {
  --mesa-cor: var(--color-status-ocupada);
  --mesa-fundo: var(--color-error-50);
}

.mesa-card--em_espera {
  --mesa-cor: var(--color-status-espera);
  --mesa-fundo: var(--color-primary-50);
}

.mesa-card.is-inativa {
  --mesa-cor: var(--color-neutral-400);
  --mesa-fundo: var(--color-neutral-100);
  opacity: 0.7;
}

.mesa-card__button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  width: 100%;
  padding: 16px 12px 14px;
  border: 1.5px solid var(--color-border-light);
  border-radius: 18px;
  background: var(--color-surface);
  transition:
    border-color 160ms ease,
    box-shadow 160ms ease;
}

.mesa-card__chairs {
  display: flex;
  justify-content: center;
  gap: 8px;
  min-height: 8px;
}

.mesa-card__chairs span {
  width: 22px;
  height: 8px;
  border-radius: 6px;
  background: var(--mesa-cor);
  opacity: 0.55;
}

.mesa-card__table {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  width: 100%;
  min-height: 72px;
  border: 2px dashed var(--mesa-cor);
  border-radius: 14px;
  background: var(--mesa-fundo);
}

.mesa-card__number {
  color: var(--color-text-title);
  font-family: var(--font-title);
  font-size: 1.15rem;
  font-weight: 700;
}

.mesa-card__code {
  color: var(--mesa-cor);
  font-size: 0.74rem;
  font-weight: 700;
}

.mesa-card__capacity {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--color-text-tertiary);
  font-size: 0.75rem;
}

.mesa-card__capacity svg {
  width: 13px;
}

.mesa-card__info {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 4px;
}

.mesa-card__status {
  color: var(--mesa-cor);
  font-size: 0.8rem;
  font-weight: 700;
}

.mesa-card__total {
  color: var(--color-text-secondary);
  font-size: 0.74rem;
}

.mesa-card__edit {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--color-surface);
  color: var(--color-text-tertiary);
  box-shadow: var(--shadow-xs);
}

.mesa-card__edit svg {
  width: 14px;
}

@media (hover: hover) and (pointer: fine) {
  .mesa-card__button:not(:disabled):hover {
    border-color: var(--mesa-cor);
    box-shadow: var(--shadow-md);
  }
  .mesa-card__edit:hover {
    color: var(--color-primary-600);
  }
}
</style>
