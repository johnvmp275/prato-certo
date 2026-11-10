<script setup>
import { onMounted, reactive, ref, watch } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonAvatar from '@/components/Common/CommonAvatar/CommonAvatar.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';

import IconUserPlus from '@/components/Icons/IconUserPlus.vue';

import { clientesService } from '@/services/crud';
import { formatarTelefone } from '@/composables/useFormat';

defineOptions({
  name: 'PdvClienteDialog',
});

const emit = defineEmits(['select', 'close']);

const dialogRef = ref(null);
const busca = ref('');
const clientes = ref([]);
const carregando = ref(false);

// Cadastro rápido de cliente
const cadastrando = ref(false);
const salvando = ref(false);
const form = reactive({ nome: '', telefone: '' });
const erros = reactive({ nome: '', telefone: '' });

async function buscar() {
  carregando.value = true;
  try {
    const resposta = await clientesService.listar({ busca: busca.value, status: 'ativo', ipp: 8 });
    clientes.value = resposta.clientes;
  } finally {
    carregando.value = false;
  }
}

function selecionar(cliente) {
  emit('select', cliente);
  dialogRef.value?.close();
}

async function cadastrar() {
  erros.nome = form.nome.trim().length >= 3 ? '' : 'Informe o nome (mínimo 3 letras).';
  erros.telefone = form.telefone.replace(/\D/g, '').length >= 10 ? '' : 'Informe o telefone com DDD.';
  if (erros.nome || erros.telefone) return;

  salvando.value = true;
  try {
    selecionar(await clientesService.cadastrar({ ...form }));
  } catch (error) {
    erros[error.campo ?? 'nome'] = error.mensagem;
  } finally {
    salvando.value = false;
  }
}

watch(busca, buscar);
onMounted(buscar);
</script>

<template>
  <CommonDialog
    ref="dialogRef"
    title="Cliente do pedido"
    description="Busque um cliente cadastrado ou cadastre um novo."
    :width="520"
    @close="emit('close')"
  >
    <div class="pdv-cliente-dialog">
      <template v-if="!cadastrando">
        <CommonSearch
          v-model="busca"
          placeholder="Nome, telefone ou e-mail..."
        />

        <CommonLoader
          v-if="carregando && !clientes.length"
          label="Buscando clientes..."
        />

        <ul
          v-else-if="clientes.length"
          class="pdv-cliente-dialog__list"
        >
          <li
            v-for="cliente in clientes"
            :key="cliente.id"
          >
            <button
              type="button"
              class="pdv-cliente-dialog__item"
              @click="selecionar(cliente)"
            >
              <CommonAvatar :name="cliente.nome" />
              <span class="pdv-cliente-dialog__info">
                <span class="pdv-cliente-dialog__name">{{ cliente.nome }}</span>
                <span class="pdv-cliente-dialog__phone">{{ formatarTelefone(cliente.telefone) }}</span>
              </span>
              <span class="pdv-cliente-dialog__select">Selecionar</span>
            </button>
          </li>
        </ul>

        <CommonEmptyState
          v-else
          title="Nenhum cliente encontrado"
          description="Cadastre o cliente em poucos segundos."
        />

        <CommonButton
          variant="soft"
          block
          @click="cadastrando = true"
        >
          <template #icon><IconUserPlus /></template>
          Cadastrar novo cliente
        </CommonButton>
      </template>

      <form
        v-else
        class="pdv-cliente-dialog__form"
        novalidate
        @submit.prevent="cadastrar"
      >
        <CommonInput
          v-model="form.nome"
          label="Nome"
          placeholder="Nome do cliente"
          :error="erros.nome"
          required
        />
        <CommonInput
          v-model="form.telefone"
          label="Telefone"
          placeholder="(11) 99999-9999"
          mask="(##) #####-####"
          inputmode="tel"
          :error="erros.telefone"
          required
        />
        <div class="pdv-cliente-dialog__form-actions">
          <CommonButton
            variant="secondary"
            @click="cadastrando = false"
          >
            Voltar
          </CommonButton>
          <CommonButton
            type="submit"
            :loading="salvando"
          >
            Cadastrar e selecionar
          </CommonButton>
        </div>
      </form>
    </div>
  </CommonDialog>
</template>

<style scoped>
.pdv-cliente-dialog {
  display: grid;
  gap: 14px;
}

.pdv-cliente-dialog__list {
  display: grid;
  gap: 4px;
}

.pdv-cliente-dialog__item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px;
  border-radius: 12px;
  text-align: left;
}

.pdv-cliente-dialog__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.pdv-cliente-dialog__name {
  color: var(--color-text-title);
  font-weight: 600;
}

.pdv-cliente-dialog__phone {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
}

.pdv-cliente-dialog__select {
  color: var(--color-primary-600);
  font-size: 0.8rem;
  font-weight: 600;
}

.pdv-cliente-dialog__form {
  display: grid;
  gap: 14px;
}

.pdv-cliente-dialog__form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

@media (hover: hover) and (pointer: fine) {
  .pdv-cliente-dialog__item:hover {
    background: var(--color-neutral-50);
  }
}
</style>
