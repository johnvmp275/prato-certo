<script setup>
import { computed, reactive, ref, watch } from 'vue';

import CommonDialog from '@/components/Common/CommonDialog/CommonDialog.vue';
import CommonTabs from '@/components/Common/CommonTabs/CommonTabs.vue';
import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';
import CommonMessageError from '@/components/Common/CommonMessageError/CommonMessageError.vue';

import IconReceipt from '@/components/Icons/IconReceipt.vue';
import IconLogOut from '@/components/Icons/IconLogOut.vue';

import { useConta } from '@/stores/useConta';
import { usePedidosCardapio } from '@/stores/usePedidosCardapio';
import { useToast } from '@/stores/useToast';
import { useFormulario } from '@/composables/useFormulario';

defineOptions({
  name: 'CardapioConta',
});

const emit = defineEmits(['close']);

const conta = useConta();
const pedidosCardapio = usePedidosCardapio();
const toast = useToast();

const dialogRef = ref(null);

/* ===========================
 * VISITANTE: ENTRAR / CRIAR CONTA
 * =========================== */
const modo = ref('entrar');
const MODOS = [
  { value: 'entrar', label: 'Entrar' },
  { value: 'cadastrar', label: 'Criar conta' },
];

const acesso = reactive({ nome: '', telefone: '', email: '', senha: '' });
const formAcesso = useFormulario(['nome', 'telefone', 'email', 'senha']);

watch(modo, () => formAcesso.limparErros());

async function enviarAcesso() {
  const cadastro = modo.value === 'cadastrar';
  const valido = formAcesso.validar({
    nome: !cadastro || acesso.nome.trim().length >= 2 ? '' : 'Informe seu nome.',
    telefone: acesso.telefone.replace(/\D/g, '').length >= 10 ? '' : 'Informe seu telefone com DDD.',
    senha: acesso.senha.length >= (cadastro ? 6 : 1) ? '' : cadastro ? 'A senha deve ter no mínimo 6 caracteres.' : 'Informe sua senha.',
  });
  if (!valido) return;

  const cliente = await formAcesso.enviar(() =>
    cadastro ? conta.cadastrar({ ...acesso }) : conta.login(acesso.telefone, acesso.senha),
  );

  if (cliente) {
    toast.success(cadastro ? `Conta criada! Bem-vindo(a), ${conta.primeiroNome}.` : `Olá, ${conta.primeiroNome}!`);
    dialogRef.value?.close();
  }
}

/* ===========================
 * CLIENTE LOGADO: MEUS PEDIDOS / MEUS DADOS
 * =========================== */
const dados = reactive({ nome: '', telefone: '', email: '', endereco: '', senhaAtual: '', novaSenha: '' });
const formDados = useFormulario(['nome', 'telefone', 'email', 'endereco', 'senhaAtual', 'novaSenha']);

function preencherDados() {
  Object.assign(dados, {
    nome: conta.cliente?.nome ?? '',
    telefone: conta.cliente?.telefone ?? '',
    email: conta.cliente?.email ?? '',
    endereco: conta.cliente?.endereco ?? '',
    senhaAtual: '',
    novaSenha: '',
  });
}

async function salvarDados() {
  const valido = formDados.validar({
    nome: dados.nome.trim().length >= 2 ? '' : 'Informe seu nome.',
    telefone: dados.telefone.replace(/\D/g, '').length >= 10 ? '' : 'Informe seu telefone com DDD.',
    senhaAtual: !dados.novaSenha || dados.senhaAtual ? '' : 'Informe a senha atual para trocar.',
    novaSenha: !dados.novaSenha || dados.novaSenha.length >= 6 ? '' : 'A senha deve ter no mínimo 6 caracteres.',
  });
  if (!valido) return;

  const cliente = await formDados.enviar(() => conta.atualizar({ ...dados }));
  if (cliente) {
    preencherDados();
    toast.success('Seus dados foram atualizados.');
  }
}

async function sair() {
  await conta.logout();
  toast.info('Você saiu da sua conta.');
  dialogRef.value?.close();
}

// Abre a janela de pedidos (fecha esta para não empilhar duas janelas)
function verPedidos() {
  emit('close');
  pedidosCardapio.abrirLista();
}

// Preenche "Meus dados" quando o cliente entra
watch(
  () => conta.isLogado,
  logado => logado && preencherDados(),
  { immediate: true },
);

const titulo = computed(() => (conta.isLogado ? `Olá, ${conta.primeiroNome}` : 'Sua conta'));
const descricao = computed(() =>
  conta.isLogado
    ? 'Mantenha seus dados atualizados para pedir mais rápido.'
    : 'Entre para preencher seus dados automaticamente e ver seus pedidos.',
);
</script>

<template>
  <CommonDialog
    ref="dialogRef"
    :title="titulo"
    :description="descricao"
    :width="520"
    @close="emit('close')"
  >
    <!-- VISITANTE -->
    <form
      v-if="!conta.isLogado"
      class="cardapio-conta__form"
      novalidate
      @submit.prevent="enviarAcesso"
    >
      <CommonTabs
        v-model="modo"
        :tabs="MODOS"
        variant="underline"
      />

      <CommonInput
        v-if="modo === 'cadastrar'"
        v-model="acesso.nome"
        label="Seu nome"
        autocomplete="name"
        :error="formAcesso.erros.nome"
        required
      />
      <CommonInput
        v-model="acesso.telefone"
        label="Telefone / WhatsApp"
        mask="(##) #####-####"
        inputmode="tel"
        autocomplete="tel"
        :error="formAcesso.erros.telefone"
        required
      />
      <CommonInput
        v-if="modo === 'cadastrar'"
        v-model="acesso.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        hint="Opcional"
        :error="formAcesso.erros.email"
      />
      <CommonInput
        v-model="acesso.senha"
        label="Senha"
        type="password"
        :autocomplete="modo === 'cadastrar' ? 'new-password' : 'current-password'"
        :hint="modo === 'cadastrar' ? 'Mínimo de 6 caracteres' : ''"
        :error="formAcesso.erros.senha"
        required
      />
      <CommonMessageError :error="formAcesso.erroGeral.value" />

      <CommonButton
        type="submit"
        size="lg"
        block
        :loading="formAcesso.salvando.value"
      >
        {{ modo === 'cadastrar' ? 'Criar conta' : 'Entrar' }}
      </CommonButton>
    </form>

    <!-- CLIENTE LOGADO -->
    <div
      v-else
      class="cardapio-conta__logged"
    >
      <CommonButton
        variant="secondary"
        block
        @click="verPedidos"
      >
        <template #icon><IconReceipt /></template>
        Ver meus pedidos
      </CommonButton>

      <!-- MEUS DADOS -->
      <form
        class="cardapio-conta__form"
        novalidate
        @submit.prevent="salvarDados"
      >
        <CommonInput
          v-model="dados.nome"
          label="Nome"
          autocomplete="name"
          :error="formDados.erros.nome"
          required
        />
        <CommonInput
          v-model="dados.telefone"
          label="Telefone / WhatsApp"
          mask="(##) #####-####"
          inputmode="tel"
          autocomplete="tel"
          hint="É com ele que você entra na conta"
          :error="formDados.erros.telefone"
          required
        />
        <CommonInput
          v-model="dados.email"
          label="E-mail"
          type="email"
          autocomplete="email"
          :error="formDados.erros.email"
        />
        <CommonInput
          v-model="dados.endereco"
          label="Endereço de entrega"
          placeholder="Rua, número, bairro e complemento"
          autocomplete="street-address"
          hint="Usado para preencher o carrinho nas entregas"
          :error="formDados.erros.endereco"
        />
        <div class="cardapio-conta__password">
          <CommonInput
            v-model="dados.senhaAtual"
            label="Senha atual"
            type="password"
            autocomplete="current-password"
            :error="formDados.erros.senhaAtual"
          />
          <CommonInput
            v-model="dados.novaSenha"
            label="Nova senha"
            type="password"
            autocomplete="new-password"
            hint="Deixe vazio para manter"
            :error="formDados.erros.novaSenha"
          />
        </div>
        <CommonMessageError :error="formDados.erroGeral.value" />

        <CommonButton
          type="submit"
          block
          :loading="formDados.salvando.value"
        >
          Salvar dados
        </CommonButton>
      </form>

      <CommonButton
        variant="ghost"
        block
        @click="sair"
      >
        <template #icon><IconLogOut /></template>
        Sair da conta
      </CommonButton>
    </div>
  </CommonDialog>
</template>

<style scoped>
.cardapio-conta__form,
.cardapio-conta__logged {
  display: grid;
  gap: 14px;
}

.cardapio-conta__password {
  display: grid;
  gap: 14px;
}

@media (min-width: 640px) {
  .cardapio-conta__password {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
