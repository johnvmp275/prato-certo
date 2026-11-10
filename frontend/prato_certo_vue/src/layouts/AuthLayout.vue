<script setup>
import CommonThemeToggle from '@/components/Common/CommonThemeToggle/CommonThemeToggle.vue';

import IconCheck from '@/components/Icons/IconCheck.vue';

const DESTAQUES = [
  'Comandas por mesa, balcão e delivery',
  'Pagamento em dinheiro, cartão e PIX',
  'Cardápio online integrado ao PDV',
];
</script>

<template>
  <div class="auth-layout">
    <section class="auth-layout__brand">
      <div class="auth-layout__brand-content">
        <img
          src="/favicon.svg"
          alt=""
          class="auth-layout__logo"
          aria-hidden="true"
        />
        <h2 class="auth-layout__title">Prato Certo</h2>
        <p class="auth-layout__subtitle">PDV e cardápio online para restaurantes.</p>

        <ul class="auth-layout__list">
          <li
            v-for="item in DESTAQUES"
            :key="item"
          >
            <IconCheck aria-hidden="true" />
            {{ item }}
          </li>
        </ul>
      </div>

      <div
        class="auth-layout__plates"
        aria-hidden="true"
      >
        <span class="auth-layout__plate auth-layout__plate--1" />
        <span class="auth-layout__plate auth-layout__plate--2" />
        <span class="auth-layout__plate auth-layout__plate--3" />
      </div>
    </section>

    <main class="auth-layout__content">
      <CommonThemeToggle class="auth-layout__theme" />
      <slot />
    </main>
  </div>
</template>

<style scoped>
.auth-layout {
  display: grid;
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--color-surface);
}

.auth-layout__brand {
  display: none;
}

.auth-layout__content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
}

/* Botão de tema no canto superior direito da área do formulário */
.auth-layout__theme {
  position: absolute;
  top: 16px;
  right: 16px;
}

@media (min-width: 1024px) {
  .auth-layout {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  }

  .auth-layout__brand {
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
    padding: 56px;
    background: linear-gradient(150deg, var(--color-primary-500), var(--color-primary-700));
    color: #ffffff;
  }

  .auth-layout__brand-content {
    position: relative;
    z-index: 1;
    max-width: 420px;
  }

  .auth-layout__logo {
    width: 56px;
    height: 56px;
    margin-bottom: 24px;
    border-radius: 16px;
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.25);
  }

  .auth-layout__title {
    color: #ffffff;
    font-size: 2.4rem;
  }

  .auth-layout__subtitle {
    margin-top: 8px;
    font-size: 1.05rem;
    opacity: 0.9;
  }

  .auth-layout__list {
    display: grid;
    gap: 12px;
    margin-top: 32px;
  }

  .auth-layout__list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
  }

  .auth-layout__list svg {
    width: 22px;
    padding: 3px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
  }

  /* PRATOS DECORATIVOS */
  .auth-layout__plate {
    position: absolute;
    border-radius: 50%;
    border: 18px solid rgba(255, 255, 255, 0.12);
  }

  .auth-layout__plate--1 {
    right: -120px;
    bottom: -120px;
    width: 420px;
    height: 420px;
  }

  .auth-layout__plate--2 {
    top: -60px;
    right: 60px;
    width: 200px;
    height: 200px;
  }

  .auth-layout__plate--3 {
    bottom: 120px;
    left: -60px;
    width: 160px;
    height: 160px;
  }
}
</style>
