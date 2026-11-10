<script setup>
import { onMounted, ref, watch } from 'vue';

import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';

import IconQrCode from '@/components/Icons/IconQrCode.vue';
import IconCheck from '@/components/Icons/IconCheck.vue';

import { cardapioService } from '@/services/painel';
import { useToast } from '@/stores/useToast';
import { formatarMoeda } from '@/composables/useFormat';

defineOptions({
  name: 'CardapioPix',
});

const props = defineProps({
  codigo: {
    type: String,
    required: true,
  },
  // Total do pedido: quando muda (ex.: desconto aplicado no caixa) o QR Code é gerado de novo
  total: {
    type: Number,
    required: true,
  },
});

const toast = useToast();

const pix = ref(null);
const erro = ref('');
const carregando = ref(true);
const copiado = ref('');

async function carregar() {
  carregando.value = true;
  try {
    pix.value = await cardapioService.pix(props.codigo);
    erro.value = '';
  } catch (error) {
    pix.value = null;
    erro.value = error.mensagem;
  } finally {
    carregando.value = false;
  }
}

async function copiar(texto, tipo) {
  try {
    await navigator.clipboard.writeText(texto);
    copiado.value = tipo;
    toast.success(tipo === 'codigo' ? 'Código PIX copiado. Cole no app do seu banco.' : 'Chave PIX copiada.');
    setTimeout(() => (copiado.value = ''), 2500);
  } catch {
    toast.error('Não foi possível copiar. Selecione o texto e copie manualmente.');
  }
}

watch(() => props.total, carregar);
onMounted(carregar);
</script>

<template>
  <section class="cardapio-pix">
    <header class="cardapio-pix__header">
      <IconQrCode aria-hidden="true" />
      <div>
        <h3 class="cardapio-pix__title">Pague com PIX</h3>
        <p class="cardapio-pix__text">Escaneie o QR Code ou use o PIX copia e cola no app do seu banco.</p>
      </div>
    </header>

    <CommonLoader
      v-if="carregando && !pix"
      label="Gerando o PIX..."
    />

    <p
      v-else-if="erro"
      class="cardapio-pix__error"
    >
      {{ erro }}
    </p>

    <template v-else-if="pix">
      <div class="cardapio-pix__qr">
        <img
          :src="pix.qrCode"
          :alt="`QR Code PIX de ${formatarMoeda(pix.valor)}`"
          width="200"
          height="200"
        />
        <strong class="cardapio-pix__value tabular">{{ formatarMoeda(pix.valor) }}</strong>
        <small class="cardapio-pix__receiver">Para {{ pix.titular }}</small>
      </div>

      <div class="cardapio-pix__field">
        <span class="cardapio-pix__label">PIX copia e cola</span>
        <code class="cardapio-pix__code">{{ pix.copiaECola }}</code>
        <CommonButton
          block
          :variant="copiado === 'codigo' ? 'success' : 'primary'"
          @click="copiar(pix.copiaECola, 'codigo')"
        >
          <template
            v-if="copiado === 'codigo'"
            #icon
          >
            <IconCheck />
          </template>
          {{ copiado === 'codigo' ? 'Código copiado!' : 'Copiar código PIX' }}
        </CommonButton>
      </div>

      <div class="cardapio-pix__key">
        <div class="cell-stack">
          <span class="cardapio-pix__label">Chave PIX</span>
          <strong>{{ pix.chave }}</strong>
        </div>
        <CommonButton
          size="sm"
          variant="secondary"
          @click="copiar(pix.chave, 'chave')"
        >
          {{ copiado === 'chave' ? 'Copiada!' : 'Copiar' }}
        </CommonButton>
      </div>

      <p class="cardapio-pix__note">Depois de pagar, o restaurante confirma o recebimento e o status do pedido é atualizado aqui.</p>
    </template>
  </section>
</template>

<style scoped>
.cardapio-pix {
  display: grid;
  gap: 14px;
  padding: 16px;
  border: 1.5px solid var(--color-primary-200);
  border-radius: 16px;
  background: var(--color-primary-50);
}

.cardapio-pix__header {
  display: flex;
  gap: 12px;
}

.cardapio-pix__header svg {
  flex-shrink: 0;
  width: 26px;
  color: var(--color-primary-600);
}

.cardapio-pix__title {
  font-size: 1rem;
}

.cardapio-pix__text,
.cardapio-pix__note {
  color: var(--color-text-secondary);
  font-size: 0.8rem;
}

.cardapio-pix__error {
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--color-surface);
  color: var(--color-text-secondary);
  font-size: 0.84rem;
}

.cardapio-pix__qr {
  display: grid;
  justify-items: center;
  gap: 4px;
  padding: 16px;
  border-radius: 14px;
  background: #ffffff;
}

.cardapio-pix__qr img {
  width: 200px;
  height: 200px;
}

.cardapio-pix__value {
  color: var(--color-text-title);
  font-size: 1.3rem;
}

.cardapio-pix__receiver {
  color: var(--color-text-tertiary);
}

.cardapio-pix__field {
  display: grid;
  gap: 8px;
}

.cardapio-pix__label {
  color: var(--color-text-tertiary);
  font-size: 0.76rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.cardapio-pix__code {
  display: block;
  max-height: 64px;
  padding: 10px 12px;
  overflow-y: auto;
  border: 1px dashed var(--color-border-dark);
  border-radius: 10px;
  background: var(--color-surface);
  color: var(--color-text-primary);
  font-size: 0.74rem;
  word-break: break-all;
  user-select: all;
}

.cardapio-pix__key {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--color-surface);
}

.cardapio-pix__key strong {
  font-size: 0.88rem;
  word-break: break-all;
}
</style>
