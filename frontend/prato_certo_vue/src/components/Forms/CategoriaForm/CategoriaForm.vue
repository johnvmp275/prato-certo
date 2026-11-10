<script setup>
import { reactive } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
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
  ativo: (props.categoria?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario(['nome']);

async function salvar(close) {
  const valido = validar({
    nome: form.nome.trim().length >= 2 ? '' : 'Informe o nome da categoria.',
  });
  if (!valido) return;

  // A posição é definida arrastando na listagem: nova categoria entra no fim, a edição mantém a posição
  const dados = {
    nome: form.nome,
    status: form.ativo ? 'ativo' : 'inativo',
  };
  const categoria = await enviar(() =>
    props.categoria
      ? categoriasService.atualizar(props.categoria.id, dados)
      : categoriasService.cadastrar(dados),
  );
  if (categoria) {
    emit('saved', categoria);
    close();
  }
}
</script>

<template>
  <CommonSheet
    position="auto"
    :title="categoria ? 'Editar categoria' : 'Nova categoria'"
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
          label="Nome"
          placeholder="Ex: Lanches"
          :error="erros.nome"
          required
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
        Salvar categoria
      </CommonButton>
    </template>
  </CommonSheet>
</template>
