<script setup>
import { onMounted, reactive, ref } from 'vue';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconCheck from '@/components/Icons/IconCheck.vue';
import IconStore from '@/components/Icons/IconStore.vue';

import { configuracoesService } from '@/services/painel';
import { useFormulario } from '@/composables/useFormulario';
import { useToast } from '@/stores/useToast';

defineOptions({
  name: 'ViewConfiguracoes',
});

const toast = useToast();

const carregando = ref(true);
const form = reactive({
  nomeRestaurante: '',
  telefone: '',
  endereco: '',
  taxaServico: '',
  taxaEntrega: '',
  cardapioAberto: true,
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nomeRestaurante',
  'telefone',
  'endereco',
  'taxaServico',
  'taxaEntrega',
]);

function numero(valor) {
  return Number(String(valor).replace(',', '.'));
}

async function salvar() {
  const valido = validar({
    nomeRestaurante: form.nomeRestaurante.trim().length >= 2 ? '' : 'Informe o nome do restaurante.',
    taxaServico: numero(form.taxaServico) >= 0 && numero(form.taxaServico) <= 30 ? '' : 'Informe de 0 a 30%.',
    taxaEntrega: numero(form.taxaEntrega) >= 0 ? '' : 'A taxa não pode ser negativa.',
  });
  if (!valido) return;

  const configuracao = await enviar(() =>
    configuracoesService.atualizar({
      ...form,
      taxaServico: numero(form.taxaServico),
      taxaEntrega: numero(form.taxaEntrega),
    }),
  );
  if (configuracao) toast.success('Configurações salvas.');
}

onMounted(async () => {
  try {
    const configuracao = await configuracoesService.buscar();
    Object.assign(form, {
      ...configuracao,
      telefone: configuracao.telefone ?? '',
      endereco: configuracao.endereco ?? '',
      taxaServico: String(configuracao.taxaServico).replace('.', ','),
      taxaEntrega: String(configuracao.taxaEntrega).replace('.', ','),
    });
  } catch (error) {
    toast.error(error.mensagem);
  } finally {
    carregando.value = false;
  }
});
</script>

<template>
  <main class="view-page row">
    <CommonPageHeader
      title="Configurações"
      subtitle="Dados do restaurante, taxas e funcionamento do cardápio online."
    />

    <CommonLoader v-if="carregando" />

    <form
      v-else
      class="view-configuracoes"
      novalidate
      @submit.prevent="salvar"
    >
      <section class="view-configuracoes__section card">
        <h2 class="view-configuracoes__title">Restaurante</h2>
        <div class="form-grid">
          <CommonInput
            v-model="form.nomeRestaurante"
            class="form-grid__full"
            label="Nome do restaurante"
            :error="erros.nomeRestaurante"
            required
          />
          <CommonInput
            v-model="form.telefone"
            label="Telefone"
            :error="erros.telefone"
          />
          <CommonInput
            v-model="form.endereco"
            label="Endereço"
            :error="erros.endereco"
          />
        </div>
      </section>

      <section class="view-configuracoes__section card">
        <h2 class="view-configuracoes__title">Taxas</h2>
        <div class="form-grid">
          <CommonInput
            v-model="form.taxaServico"
            label="Taxa de serviço (%)"
            inputmode="decimal"
            hint="Aplicada em pedidos de mesa (opcional por comanda)."
            :error="erros.taxaServico"
          />
          <CommonInput
            v-model="form.taxaEntrega"
            label="Taxa de entrega (R$)"
            inputmode="decimal"
            hint="Aplicada em pedidos online para entrega."
            :error="erros.taxaEntrega"
          />
        </div>
      </section>

      <section class="view-configuracoes__section card">
        <h2 class="view-configuracoes__title">Cardápio online</h2>
        <CommonSwitch
          v-model="form.cardapioAberto"
          label="Receber pedidos online"
          description="Quando desligado, o cardápio continua visível, mas não aceita pedidos."
        />
        <a
          href="/cardapio"
          target="_blank"
          rel="noopener"
          class="view-configuracoes__link"
        >
          <IconStore aria-hidden="true" />
          Abrir cardápio online
        </a>
      </section>

      <CommonMessageError :error="erroGeral" />

      <div class="view-configuracoes__actions">
        <CommonButton
          type="submit"
          size="lg"
          :loading="salvando"
        >
          <template #icon><IconCheck /></template>
          Salvar configurações
        </CommonButton>
      </div>
    </form>
  </main>
</template>

<style scoped>
.view-configuracoes {
  display: grid;
  gap: 18px;
  max-width: 760px;
}

.view-configuracoes__section {
  display: grid;
  gap: 16px;
  padding: 22px;
}

.view-configuracoes__title {
  font-size: 1rem;
}

.view-configuracoes__link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--color-primary-600);
  font-size: 0.88rem;
  font-weight: 600;
}

.view-configuracoes__link svg {
  width: 18px;
}

.view-configuracoes__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
