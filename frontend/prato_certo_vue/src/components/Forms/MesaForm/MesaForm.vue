<script setup>
import { reactive } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { mesasService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'MesaForm',
});

const props = defineProps({
  mesa: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'close']);

const form = reactive({
  numero: props.mesa?.numero ?? '',
  capacidade: props.mesa?.capacidade ?? 4,
  ativo: (props.mesa?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'numero',
  'capacidade',
  'status',
]);

async function salvar(close) {
  const valido = validar({
    numero: Number(form.numero) >= 1 ? '' : 'Informe um número maior que zero.',
    capacidade:
      Number(form.capacidade) >= 1 && Number(form.capacidade) <= 30
        ? ''
        : 'Capacidade de 1 a 30 pessoas.',
  });
  if (!valido) return;

  const dados = {
    numero: Number(form.numero),
    capacidade: Number(form.capacidade),
    status: form.ativo ? 'ativo' : 'inativo',
  };
  const mesa = await enviar(() =>
    props.mesa ? mesasService.atualizar(props.mesa.id, dados) : mesasService.cadastrar(dados),
  );
  if (mesa) {
    emit('saved', mesa);
    close();
  }
}
</script>

<template>
  <CommonSheet
    position="auto"
    :title="mesa ? `Editar mesa ${mesa.numero}` : 'Nova mesa'"
    @close="emit('close')"
  >
    <template #content>
      <form
        class="mesa-form"
        novalidate
        @submit.prevent
      >
        <div class="mesa-form__row">
          <CommonInput
            v-model="form.numero"
            label="Número"
            type="number"
            min="1"
            :error="erros.numero"
            required
          />
          <CommonInput
            v-model="form.capacidade"
            label="Capacidade"
            type="number"
            min="1"
            max="30"
            :error="erros.capacidade"
            required
          />
        </div>
        <CommonSwitch
          v-model="form.ativo"
          label="Mesa ativa"
          description="Mesas inativas não aparecem no mapa do salão."
        />
        <CommonMessageError :error="erros.status || erroGeral" />
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
        Salvar mesa
      </CommonButton>
    </template>
  </CommonSheet>
</template>

<style scoped>
.mesa-form {
  display: grid;
  gap: 16px;
}

.mesa-form__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
</style>
