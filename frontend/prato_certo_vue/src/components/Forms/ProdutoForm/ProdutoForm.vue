<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonFoodImage from '@/components/Common/CommonFoodImage/CommonFoodImage.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconCamera from '@/components/Icons/IconCamera.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';

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

// Mesmos limites validados pelo backend
const TIPOS_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];
const TAMANHO_MAXIMO = 2 * 1024 * 1024;

const form = reactive({
  nome: props.produto?.nome ?? '',
  categoriaId: props.produto?.categoriaId ?? '',
  preco: props.produto ? String(props.produto.preco).replace('.', ',') : '',
  descricao: props.produto?.descricao ?? '',
  controlaEstoque: props.produto?.controlaEstoque ?? false,
  estoque: props.produto?.estoque ?? 0,
  ativo: (props.produto?.status ?? 'ativo') === 'ativo',
});

// Imagem: arquivo novo escolhido, pré-visualização e remoção da imagem atual
const inputArquivo = ref(null);
const arquivo = ref(null);
const preview = ref(props.produto?.imagem ?? '');
const removerImagem = ref(false);

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

function liberarPreview() {
  if (preview.value.startsWith('blob:')) URL.revokeObjectURL(preview.value);
}

function escolherArquivo(event) {
  const selecionado = event.target.files?.[0];
  event.target.value = '';
  if (!selecionado) return;

  // Validação no navegador (o backend confere o tipo real do arquivo)
  if (!TIPOS_ACEITOS.includes(selecionado.type)) {
    erros.imagem = 'Envie uma imagem JPG, PNG ou WEBP.';
    return;
  }
  if (selecionado.size > TAMANHO_MAXIMO) {
    erros.imagem = 'A imagem deve ter no máximo 2 MB.';
    return;
  }

  erros.imagem = '';
  liberarPreview();
  arquivo.value = selecionado;
  preview.value = URL.createObjectURL(selecionado);
  removerImagem.value = false;
}

function limparImagem() {
  liberarPreview();
  arquivo.value = null;
  preview.value = '';
  removerImagem.value = Boolean(props.produto?.imagem);
}

async function salvar(close) {
  const preco = Number(String(form.preco).replace(',', '.'));
  const valido = validar({
    nome: form.nome.trim().length >= 2 ? '' : 'Informe o nome do produto.',
    categoriaId: form.categoriaId ? '' : 'Selecione a categoria.',
    preco: preco > 0 ? '' : 'Informe um preço maior que zero.',
    estoque: !form.controlaEstoque || Number(form.estoque) >= 0 ? '' : 'O estoque não pode ser negativo.',
  });
  if (!valido) return;

  // Monta o envio em multipart (campos + arquivo)
  const dados = new FormData();
  dados.append('nome', form.nome);
  dados.append('categoriaId', form.categoriaId);
  dados.append('preco', preco);
  dados.append('descricao', form.descricao ?? '');
  dados.append('controlaEstoque', form.controlaEstoque ? '1' : '0');
  dados.append('estoque', form.controlaEstoque ? form.estoque : 0);
  dados.append('status', form.ativo ? 'ativo' : 'inativo');
  if (arquivo.value) dados.append('imagem', arquivo.value);
  if (removerImagem.value) dados.append('removerImagem', '1');

  const produto = await enviar(() => produtosService.salvarComImagem(props.produto?.id, dados));
  if (produto) {
    emit('saved', produto);
    close();
  }
}

onBeforeUnmount(liberarPreview);
</script>

<template>
  <CommonSheet
    position="auto"
    :title="produto ? 'Editar produto' : 'Novo produto'"
    description="Produtos ativos aparecem no PDV e no cardápio online."
    @close="emit('close')"
  >
    <template #content>
      <form
        class="form-grid"
        novalidate
        @submit.prevent
      >
        <!-- UPLOAD DA IMAGEM -->
        <div class="produto-form__imagem form-grid__full">
          <CommonFoodImage
            :src="preview"
            :alt="form.nome"
            :size="96"
          />
          <div class="produto-form__imagem-info">
            <p class="produto-form__imagem-titulo">Foto do produto</p>
            <p class="produto-form__imagem-dica">JPG, PNG ou WEBP de até 2 MB.</p>
            <div class="produto-form__imagem-acoes">
              <CommonButton
                variant="soft"
                size="sm"
                @click="inputArquivo?.click()"
              >
                <template #icon><IconCamera /></template>
                {{ preview ? 'Trocar imagem' : 'Escolher imagem' }}
              </CommonButton>
              <CommonButton
                v-if="preview"
                variant="ghost"
                size="sm"
                @click="limparImagem"
              >
                <template #icon><IconTrash /></template>
                Remover
              </CommonButton>
            </div>
            <input
              ref="inputArquivo"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="sr-only"
              aria-label="Escolher imagem do produto"
              @change="escolherArquivo"
            />
            <CommonMessageError :error="erros.imagem" />
          </div>
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
        Salvar produto
      </CommonButton>
    </template>
  </CommonSheet>
</template>

<style scoped>
.produto-form__imagem {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px;
  border: 1.5px dashed var(--color-primary-200);
  border-radius: 16px;
  background: var(--color-primary-50);
}

.produto-form__imagem-info {
  display: grid;
  gap: 2px;
  min-width: 0;
}

.produto-form__imagem-titulo {
  color: var(--color-text-title);
  font-size: 0.9rem;
  font-weight: 600;
}

.produto-form__imagem-dica {
  color: var(--color-text-tertiary);
  font-size: 0.78rem;
}

.produto-form__imagem-acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}
</style>
