<script setup>
import { reactive, ref } from 'vue';

import { useRoute, useRouter } from 'vue-router';

import CommonInput from '@/components/Common/CommonInput/CommonInput.vue';
import CommonButton from '@/components/Common/CommonButton/CommonButton.vue';

import IconMail from '@/components/Icons/IconMail.vue';
import IconLock from '@/components/Icons/IconLock.vue';
import IconAlert from '@/components/Icons/IconAlert.vue';

import { useAuth } from '@/stores/useAuth';

defineOptions({
  name: 'ViewLogin',
});

const auth = useAuth();
const route = useRoute();
const router = useRouter();

const form = reactive({
  email: '',
  senha: '',
});
const erros = reactive({
  email: '',
  senha: '',
});
const erroGeral = ref('');
const carregando = ref(false);

// Validação no navegador antes de enviar (o backend valida de novo)
function validar() {
  erros.email = /^\S+@\S+\.\S+$/.test(form.email.trim()) ? '' : 'Informe um e-mail válido.';
  erros.senha = form.senha ? '' : 'Informe sua senha.';
  return !erros.email && !erros.senha;
}

async function entrar() {
  erroGeral.value = '';
  if (!validar()) return;

  carregando.value = true;
  try {
    const usuario = await auth.login(form.email.trim(), form.senha);
    const destino = route.query.redirect || (usuario.perfil === 'administrador' ? '/painel' : '/painel/pdv');
    router.push(destino);
  } catch (error) {
    erroGeral.value = error.mensagem;
  } finally {
    carregando.value = false;
  }
}
</script>

<template>
  <div class="view-login">
    <div class="view-login__header">
      <img
        src="/favicon.svg"
        alt=""
        class="view-login__logo"
        aria-hidden="true"
      />
      <h1 class="view-login__title">Bem-vindo de volta</h1>
      <p class="view-login__subtitle">Entre com seu usuário para abrir o PDV.</p>
    </div>

    <form
      class="view-login__form"
      novalidate
      @submit.prevent="entrar"
    >
      <p
        v-if="erroGeral"
        class="view-login__alert"
        role="alert"
      >
        <IconAlert aria-hidden="true" />
        {{ erroGeral }}
      </p>

      <CommonInput
        v-model="form.email"
        label="E-mail"
        type="email"
        autocomplete="username"
        placeholder="voce@restaurante.com"
        :error="erros.email"
        required
      >
        <template #input-prepend>
          <IconMail />
        </template>
      </CommonInput>

      <CommonInput
        v-model="form.senha"
        label="Senha"
        type="password"
        autocomplete="current-password"
        placeholder="Sua senha"
        :error="erros.senha"
        required
      >
        <template #input-prepend>
          <IconLock />
        </template>
      </CommonInput>

      <CommonButton
        type="submit"
        size="lg"
        block
        :loading="carregando"
      >
        Entrar
      </CommonButton>
    </form>

    <p class="view-login__hint">
      Teste: <strong>admin@pratocerto.com</strong> ou <strong>atendente@pratocerto.com</strong> · senha
      <strong>123456</strong>
    </p>

    <RouterLink
      to="/"
      class="view-login__link"
    >
      Ver cardápio online →
    </RouterLink>
  </div>
</template>

<style scoped>
.view-login {
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 100%;
  max-width: 400px;
}

.view-login__logo {
  width: 52px;
  height: 52px;
  margin-bottom: 20px;
  border-radius: 16px;
  box-shadow: var(--shadow-primary);
}

.view-login__title {
  font-size: 1.6rem;
}

.view-login__subtitle {
  margin-top: 6px;
  color: var(--color-text-secondary);
}

.view-login__form {
  display: grid;
  gap: 16px;
}

.view-login__alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  background: var(--color-error-50);
  color: var(--color-error-700);
  font-size: 0.86rem;
}

.view-login__alert svg {
  width: 18px;
  flex-shrink: 0;
}

.view-login__hint {
  padding: 12px 14px;
  border: 1px dashed var(--color-border-dark);
  border-radius: 12px;
  color: var(--color-text-secondary);
  font-size: 0.8rem;
  line-height: 1.6;
}

.view-login__link {
  color: var(--color-primary-600);
  font-size: 0.88rem;
  font-weight: 600;
}
</style>
