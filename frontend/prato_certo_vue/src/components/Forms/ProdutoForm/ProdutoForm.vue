<script setup>
import { computed, reactive } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { produtosService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'ProdutoForm',
});

const props = defineProps({
  produto: {
    type: Object,
    default: null,
  },
  categorias: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['saved', 'close']);

const form = reactive({
  nome: props.produto?.nome ?? '',
  categoriaId: props.produto?.categoriaId ?? '',
  preco: props.produto ? String(props.produto.preco).replace('.', ',') : '',
  descricao: props.produto?.descricao ?? '',
  imagem: props.produto?.imagem ?? '',
  controlaEstoque: props.produto?.controlaEstoque ?? false,
  estoque: props.produto?.estoque ?? 0,
  ativo: (props.produto?.status ?? 'ativo') === 'ativo',
});

const opcoesCategorias = computed(() =>
  props.categorias.filter(item => item.status === 'ativo').map(item => ({ value: item.id, label: item.nome })),
);

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nome',
  'categoriaId',
  'preco',
  'descricao',
  'imagem',
  'estoque',
]);

async function salvar(close) {
  const preco = Number(String(form.preco).replace(',', '.'));
  const valido = validar({
    nome: form.nome.trim().length >= 2 ? '' : 'Informe o nome do produto.',
    categoriaId: form.categoriaId ? '' : 'Selecione a categoria.',
    preco: preco > 0 ? '' : 'Informe um preço maior que zero.',
    imagem: !form.imagem || /^https?:\/\//.test(form.imagem) ? '' : 'A imagem deve ser uma URL (http/https).',
    estoque: !form.controlaEstoque || Number(form.estoque) >= 0 ? '' : 'O estoque não pode ser negativo.',
  });
  if (!valido) return;

  const dados = { ...form, preco, status: form.ativo ? 'ativo' : 'inativo' };
  const produto = await enviar(() =>
    props.produto ? produtosService.atualizar(props.produto.id, dados) : produtosService.cadastrar(dados),
  );
  if (produto) {
    emit('saved', produto);
    close();
  }
}
</script>

<template>
  <CommonDialog
    :title="produto ? 'Editar produto' : 'Novo produto'"
    description="Produtos ativos aparecem no PDV e no cardápio online."
    :width="600"
    @close="emit('close')"
  >
    <form
      class="form-grid"
      novalidate
      @submit.prevent
    >
      <div class="produto-form__preview form-grid__full">
        <CommonFoodImage
          :src="form.imagem"
          :alt="form.nome"
          :size="76"
        />
        <CommonInput
          v-model="form.imagem"
          label="URL da imagem"
          placeholder="https://..."
          :error="erros.imagem"
        />
      </div>
      <CommonInput
        v-model="form.nome"
        class="form-grid__full"
        label="Nome"
        :error="erros.nome"
        required
      />
      <CommonSelect
        v-model="form.categoriaId"
        label="Categoria"
        placeholder="Selecione..."
        :options="opcoesCategorias"
        :error="erros.categoriaId"
        required
      />
      <CommonInput
        v-model="form.preco"
        label="Preço (R$)"
        inputmode="decimal"
        placeholder="0,00"
        :error="erros.preco"
        required
      />
      <CommonInput
        v-model="form.descricao"
        class="form-grid__full"
        label="Descrição"
        mode="textarea"
        maxlength="255"
        :error="erros.descricao"
      />
      <CommonSwitch
        v-model="form.controlaEstoque"
        class="form-grid__full"
        label="Controlar estoque"
        description="O PDV bloqueia vendas acima da quantidade disponível."
      />
      <CommonInput
        v-if="form.controlaEstoque"
        v-model="form.estoque"
        label="Quantidade em estoque"
        type="number"
        min="0"
        :error="erros.estoque"
      />
      <CommonSwitch
        v-model="form.ativo"
        class="form-grid__full"
        label="Produto ativo"
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
        Salvar produto
      </CommonButton>
    </template>
  </CommonDialog>
</template>

<style scoped>
.produto-form__preview {
  display: flex;
  align-items: flex-end;
  gap: 14px;
}

.produto-form__preview > :last-child {
  flex: 1;
}
</style>
