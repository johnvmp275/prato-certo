<script setup>
import { reactive } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { cuponsService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'CupomForm',
});

const props = defineProps({
  cupom: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'close']);

const TIPOS = [
  { value: 'percentual', label: 'Percentual (%)' },
  { value: 'fixo', label: 'Valor fixo (R$)' },
];

const form = reactive({
  codigo: props.cupom?.codigo ?? '',
  tipo: props.cupom?.tipo ?? 'percentual',
  valor: props.cupom ? String(props.cupom.valor).replace('.', ',') : '',
  validade: props.cupom?.validade ?? '',
  ativo: (props.cupom?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'codigo',
  'tipo',
  'valor',
  'validade',
]);

async function salvar(close) {
  const valor = Number(String(form.valor).replace(',', '.'));
  const valido = validar({
    codigo: /^[A-Za-z0-9]{3,30}$/.test(form.codigo)
      ? ''
      : 'Use de 3 a 30 letras ou números, sem espaços.',
    valor:
      valor > 0 && (form.tipo !== 'percentual' || valor <= 100)
        ? ''
        : 'Informe um valor válido (percentual até 100).',
  });
  if (!valido) return;

  const dados = { ...form, valor, status: form.ativo ? 'ativo' : 'inativo' };
  const cupom = await enviar(() =>
    props.cupom ? cuponsService.atualizar(props.cupom.id, dados) : cuponsService.cadastrar(dados),
  );
  if (cupom) {
    emit('saved', cupom);
    close();
  }
}
</script>

<template>
  <CommonSheet
    position="auto"
    :title="cupom ? 'Editar cupom' : 'Novo cupom'"
    @close="emit('close')"
  >
    <template #content>
      <form
        class="form-grid"
        novalidate
        @submit.prevent
      >
        <CommonInput
          v-model="form.codigo"
          class="form-grid__full"
          label="Código"
          placeholder="Ex: BEMVINDO10"
          style="text-transform: uppercase"
          :error="erros.codigo"
          required
        />
        <CommonSelect
          v-model="form.tipo"
          label="Tipo"
          :options="TIPOS"
          :error="erros.tipo"
        />
        <CommonInput
          v-model="form.valor"
          :label="form.tipo === 'percentual' ? 'Desconto (%)' : 'Desconto (R$)'"
          inputmode="decimal"
          :error="erros.valor"
          required
        />
        <CommonInput
          v-model="form.validade"
          class="form-grid__full"
          label="Validade"
          type="date"
          hint="Deixe em branco para não expirar."
          :error="erros.validade"
        />
        <CommonSwitch
          v-model="form.ativo"
          class="form-grid__full"
          label="Cupom ativo"
        />
        <CommonMessageError
          class="form-grid__full"
          :error="erroGeral"
        />
      </form>
    </template>

    <template #footer="{ close }">
      <CommonButton
        variant="secondary"
        @click="close"
      >
        Cancelar
      </CommonButton>
      <CommonButton
        :loading="salvando"
        @click="salvar(close)"
      >
        Salvar cupom
      </CommonButton>
    </template>
  </CommonSheet>
</template>
