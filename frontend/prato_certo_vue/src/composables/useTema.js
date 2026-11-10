// src/composables/useTema.js

import { computed, ref } from 'vue';

// Mesma chave usada no script do index.html (aplica o tema antes da página aparecer, sem piscar)
const CHAVE = 'prato-certo:tema';

const sistemaEscuro = window.matchMedia('(prefers-color-scheme: dark)');

function lerSalvo() {
  try {
    const salvo = localStorage.getItem(CHAVE);
    return salvo === 'escuro' || salvo === 'claro' ? salvo : null;
  } catch {
    return null;
  }
}

// null = a pessoa nunca escolheu: segue o tema do sistema operacional
const escolhido = ref(lerSalvo());
const sistema = ref(sistemaEscuro.matches ? 'escuro' : 'claro');

const tema = computed(() => escolhido.value ?? sistema.value);

function aplicar() {
  document.documentElement.dataset.theme = tema.value === 'escuro' ? 'dark' : 'light';
}

// Se a pessoa não escolheu, acompanha quando o sistema troca de tema
sistemaEscuro.addEventListener('change', evento => {
  sistema.value = evento.matches ? 'escuro' : 'claro';
  aplicar();
});

aplicar();

/**
 * Tema claro/escuro do sistema (painel e cardápio), salvo no localStorage.
 */
export function useTema() {
  const escuro = computed(() => tema.value === 'escuro');

  function alternar() {
    escolhido.value = escuro.value ? 'claro' : 'escuro';
    try {
      localStorage.setItem(CHAVE, escolhido.value);
    } catch {
      // Armazenamento indisponível: troca só até recarregar a página
    }
    aplicar();
  }

  return { tema, escuro, alternar };
}
