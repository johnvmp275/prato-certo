<script setup>
import { onMounted, ref } from 'vue';

import CommonPageHeader from '@/components/Common/CommonPageHeader/CommonPageHeader.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonIconButton from '@/components/Common/CommonIconButton/CommonIconButton.vue';
import CommonSearch from '@/components/Common/CommonSearch/CommonSearch.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonTable from '@/components/Common/CommonTable/CommonTable.vue';
import CommonPagination from '@/components/Common/CommonPagination/CommonPagination.vue';
import CommonLoader from '@/components/Common/CommonLoader/CommonLoader.vue';
import CommonEmptyState from '@/components/Common/CommonEmptyState/CommonEmptyState.vue';
import CommonStatusBadge from '@/components/Common/CommonStatusBadge/CommonStatusBadge.vue';
import CommonBadge from '@/components/Common/CommonBadge/CommonBadge.vue';
import CommonAvatar from '@/components/Common/CommonAvatar/CommonAvatar.vue';
import CommonConfirm from '@/components/Common/CommonConfirm/CommonConfirm.vue';

import UsuarioForm from '@/components/Forms/UsuarioForm/UsuarioForm.vue';

import IconUserPlus from '@/components/Icons/IconUserPlus.vue';
import IconPencil from '@/components/Icons/IconPencil.vue';
import IconTrash from '@/components/Icons/IconTrash.vue';
import IconUserCog from '@/components/Icons/IconUserCog.vue';

import { usuariosService } from '@/services/crud';
import { useListagem } from '@/composables/useListagem';
import { useAuth } from '@/stores/useAuth';
import { useToast } from '@/stores/useToast';
import { formatarData } from '@/composables/useFormat';

defineOptions({
  name: 'ViewUsuarios',
});

const auth = useAuth();
const toast = useToast();

const { itens, paginacao, carregando, busca, pagina, filtros, carregar } = useListagem(
  usuariosService.listar,
  'usuarios',
  { status: 'ativo' },
);

const COLUNAS = [
  { key: 'usuario', label: 'Usuário' },
  { key: 'perfil', label: 'Perfil' },
  { key: 'cadastro', label: 'Cadastro' },
  { key: 'status', label: 'Status' },
  { key: 'acoes', label: '', align: 'right' },
];

const STATUS = [
  { value: 'ativo', label: 'Ativos' },
  { value: 'inativo', label: 'Inativos' },
  { value: '', label: 'Todos' },
];

const formAberto = ref(false);
const usuarioEditar = ref(null);
const usuarioInativar = ref(null);

function abrirForm(usuario = null) {
  usuarioEditar.value = usuario;
  formAberto.value = true;
}

function salvo() {
  toast.success('Usuário salvo com sucesso.');
  carregar();
}

async function inativar() {
  try {
    await usuariosService.inativar(usuarioInativar.value.id);
    toast.success('Usuário inativado.');
    carregar();
  } catch (error) {
    toast.error(error.mensagem);
  }
}

onMounted(carregar);
</script>

<template>
  <main class="view-page row">
    <CommonPageHeader
      title="Usuários"
      subtitle="Quem acessa o painel e o PDV, com o perfil de cada um."
    >
      <template #actions>
        <CommonButton @click="abrirForm()">
          <template #icon><IconUserPlus /></template>
          Novo usuário
        </CommonButton>
      </template>
    </CommonPageHeader>

    <section class="card">
      <div class="list-toolbar">
        <CommonSearch
          v-model="busca"
          placeholder="Buscar por nome ou e-mail..."
        />
        <CommonSelect
          v-model="filtros.status"
          :options="STATUS"
          aria-label="Filtrar por status"
        />
      </div>

      <CommonLoader v-if="carregando && !itens.length" />

      <CommonTable
        v-else-if="itens.length"
        :columns="COLUNAS"
      >
        <tr
          v-for="usuario in itens"
          :key="usuario.id"
          class="common-table-row"
        >
          <td class="common-table-cell">
            <span class="view-usuarios__user">
              <CommonAvatar
                :name="usuario.nome"
                :size="34"
              />
              <span class="cell-stack">
                <strong>
                  {{ usuario.nome }}
                  <template v-if="usuario.id === auth.usuario?.id">(você)</template>
                </strong>
                <small>{{ usuario.email }}</small>
              </span>
            </span>
          </td>
          <td class="common-table-cell">
            <CommonBadge
              :label="usuario.perfilDescricao"
              :variant="usuario.perfil === 'administrador' ? 'primary' : 'info'"
            />
          </td>
          <td class="common-table-cell">{{ formatarData(usuario.criadoEm) }}</td>
          <td class="common-table-cell"><CommonStatusBadge :status="usuario.status" /></td>
          <td class="common-table-cell">
            <div class="cell-actions">
              <CommonIconButton
                label="Editar usuário"
                @click="abrirForm(usuario)"
              >
                <IconPencil />
              </CommonIconButton>
              <CommonIconButton
                v-if="usuario.status === 'ativo' && usuario.id !== auth.usuario?.id"
                label="Inativar usuário"
                variant="danger"
                @click="usuarioInativar = usuario"
              >
                <IconTrash />
              </CommonIconButton>
            </div>
          </td>
        </tr>
      </CommonTable>

      <CommonEmptyState
        v-else
        :icon="IconUserCog"
        title="Nenhum usuário encontrado"
      />

      <CommonPagination
        :paginacao="paginacao"
        item-label="usuários"
        @change="pagina = $event"
      />
    </section>

    <UsuarioForm
      v-if="formAberto"
      :usuario="usuarioEditar"
      @saved="salvo"
      @close="formAberto = false"
    />

    <CommonConfirm
      v-if="usuarioInativar"
      title="Inativar usuário?"
      :description="`${usuarioInativar.nome} não conseguirá mais acessar o sistema.`"
      confirm-label="Inativar"
      :action="inativar"
      @close="usuarioInativar = null"
    />
  </main>
</template>

<style scoped>
.view-usuarios__user {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>
