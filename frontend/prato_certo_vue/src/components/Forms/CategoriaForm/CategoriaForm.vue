<script setup>
import { reactive } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { categoriasService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'CategoriaForm',
});

const props = defineProps({
  categoria: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'close']);

const form = reactive({
  nome: props.categoria?.nome ?? '',
  ordem: props.categoria?.ordem ?? 0,
  ativo: (props.categoria?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario(['nome', 'ordem']);

async function salvar(close) {
  const valido = validar({
    nome: form.nome.trim().length >= 2 ? '' : 'Informe o nome da categoria.',
    ordem: Number(form.ordem) >= 0 ? '' : 'A ordem não pode ser negativa.',
  });
  if (!valido) return;

  const dados = { nome: form.nome, ordem: Number(form.ordem), status: form.ativo ? 'ativo' : 'inativo' };
  const categoria = await enviar(() =>
    props.categoria ? categoriasService.atualizar(props.categoria.id, dados) : categoriasService.cadastrar(dados),
  );
  if (categoria) {
    emit('saved', categoria);
    close();
  }
}
</script>

<template>
  <CommonDialog
    :title="categoria ? 'Editar categoria' : 'Nova categoria'"
    :width="440"
    @close="emit('close')"
  >
    <form
      class="form-grid"
      novalidate
      @submit.prevent
    >
      <CommonInput
        v-model="form.nome"
        class="form-grid__full"
        label="Nome"
        placeholder="Ex: Lanches"
        :error="erros.nome"
        required
      />
      <CommonInput
        v-model="form.ordem"
        class="form-grid__full"
        label="Ordem de exibição"
        type="number"
        min="0"
        hint="Menor número aparece primeiro no PDV e no cardápio."
        :error="erros.ordem"
      />
      <CommonSwitch
        v-model="form.ativo"
        class="form-grid__full"
        label="Categoria ativa"
      />
      <CommonMessageError
        class="form-grid__full"
        :error="erroGeral"
      />
    </form>

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
        Salvar categoria
      </CommonButton>
    </template>
  </CommonDialog>
</template>
