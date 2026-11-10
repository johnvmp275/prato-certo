<script setup>
import { reactive } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { clientesService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'ClienteForm',
});

const props = defineProps({
  cliente: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'close']);

const form = reactive({
  nome: props.cliente?.nome ?? '',
  telefone: props.cliente?.telefone ?? '',
  email: props.cliente?.email ?? '',
  documento: props.cliente?.documento ?? '',
  endereco: props.cliente?.endereco ?? '',
  ativo: (props.cliente?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nome',
  'telefone',
  'email',
  'documento',
  'endereco',
]);

async function salvar(close) {
  const valido = validar({
    nome: form.nome.trim().length >= 3 ? '' : 'Informe o nome (mínimo 3 letras).',
    telefone: form.telefone.replace(/\D/g, '').length >= 10 ? '' : 'Informe o telefone com DDD.',
    email: !form.email || /^\S+@\S+\.\S+$/.test(form.email) ? '' : 'Informe um e-mail válido.',
    documento:
      !form.documento || form.documento.replace(/\D/g, '').length === 11
        ? ''
        : 'O CPF deve ter 11 dígitos.',
  });
  if (!valido) return;

  const dados = { ...form, status: form.ativo ? 'ativo' : 'inativo' };
  const cliente = await enviar(() =>
    props.cliente
      ? clientesService.atualizar(props.cliente.id, dados)
      : clientesService.cadastrar(dados),
  );
  if (cliente) {
    emit('saved', cliente);
    close();
  }
}
</script>

<template>
  <CommonSheet
    position="auto"
    :title="cliente ? 'Editar cliente' : 'Novo cliente'"
    description="Os dados do cliente ficam vinculados ao histórico de pedidos."
    @close="emit('close')"
  >
    <template #content>
      <form
        class="form-grid"
        novalidate
        @submit.prevent
      >
        <CommonInput
          v-model="form.nome"
          class="form-grid__full"
          label="Nome completo"
          :error="erros.nome"
          required
        />
        <CommonInput
          v-model="form.telefone"
          label="Telefone"
          mask="(##) #####-####"
          inputmode="tel"
          placeholder="(11) 99999-9999"
          :error="erros.telefone"
          required
        />
        <CommonInput
          v-model="form.documento"
          label="CPF"
          mask="###.###.###-##"
          inputmode="numeric"
          placeholder="Opcional"
          :error="erros.documento"
        />
        <CommonInput
          v-model="form.email"
          class="form-grid__full"
          label="E-mail"
          type="email"
          placeholder="Opcional"
          :error="erros.email"
        />
        <CommonInput
          v-model="form.endereco"
          class="form-grid__full"
          label="Endereço"
          placeholder="Rua, número, bairro"
          :error="erros.endereco"
        />
        <CommonSwitch
          v-if="cliente"
          v-model="form.ativo"
          class="form-grid__full"
          label="Cliente ativo"
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
        Salvar cliente
      </CommonButton>
    </template>
  </CommonSheet>
</template>
