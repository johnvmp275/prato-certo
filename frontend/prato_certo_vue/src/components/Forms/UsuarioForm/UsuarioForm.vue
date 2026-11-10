<script setup>
import { reactive } from 'vue';

import CommonSheet from '@/components/Common/CommonSheet/CommonSheet.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonSelect from '@/components/Common/CommonSelect/CommonSelect.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonSwitch from '@/components/Common/CommonSwitch/CommonSwitch.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import { usuariosService } from '@/services/crud';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'UsuarioForm',
});

const props = defineProps({
  usuario: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'close']);

const PERFIS = [
  { value: 'atendente', label: 'Atendente (opera o PDV)' },
  { value: 'administrador', label: 'Administrador (acesso total)' },
];

const form = reactive({
  nome: props.usuario?.nome ?? '',
  email: props.usuario?.email ?? '',
  perfil: props.usuario?.perfil ?? 'atendente',
  senha: '',
  ativo: (props.usuario?.status ?? 'ativo') === 'ativo',
});

const { erros, erroGeral, salvando, validar, enviar } = useFormulario([
  'nome',
  'email',
  'perfil',
  'senha',
  'status',
]);

async function salvar(close) {
  const valido = validar({
    nome: form.nome.trim().length >= 3 ? '' : 'Informe o nome (mínimo 3 letras).',
    email: /^\S+@\S+\.\S+$/.test(form.email) ? '' : 'Informe um e-mail válido.',
    senha:
      (!props.usuario && form.senha.length < 6) || (form.senha && form.senha.length < 6)
        ? 'A senha deve ter no mínimo 6 caracteres.'
        : '',
  });
  if (!valido) return;

  const dados = { ...form, status: form.ativo ? 'ativo' : 'inativo' };
  const usuario = await enviar(() =>
    props.usuario
      ? usuariosService.atualizar(props.usuario.id, dados)
      : usuariosService.cadastrar(dados),
  );
  if (usuario) {
    emit('saved', usuario);
    close();
  }
}
</script>

<template>
  <CommonSheet
    position="auto"
    :title="usuario ? 'Editar usuário' : 'Novo usuário'"
    description="O perfil define o que o usuário pode acessar no sistema."
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
          :error="erros.nome"
          required
        />
        <CommonInput
          v-model="form.email"
          class="form-grid__full"
          label="E-mail (login)"
          type="email"
          autocomplete="off"
          :error="erros.email"
          required
        />
        <CommonSelect
          v-model="form.perfil"
          class="form-grid__full"
          label="Perfil"
          :options="PERFIS"
          :error="erros.perfil"
          required
        />
        <CommonInput
          v-model="form.senha"
          class="form-grid__full"
          :label="usuario ? 'Nova senha' : 'Senha'"
          type="password"
          autocomplete="new-password"
          :hint="usuario ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 6 caracteres.'"
          :error="erros.senha"
          :required="!usuario"
        />
        <CommonSwitch
          v-if="usuario"
          v-model="form.ativo"
          class="form-grid__full"
          label="Usuário ativo"
        />
        <CommonMessageError
          class="form-grid__full"
          :error="erros.status || erroGeral"
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
        Salvar usuário
      </CommonButton>
    </template>
  </CommonSheet>
</template>
