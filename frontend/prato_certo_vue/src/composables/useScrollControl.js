import { ref } from 'vue';
import { useScrollLock } from '@vueuse/core';

const scrollbarWidth = ref(0);
const isScrollLocked = useScrollLock(document.body);

// Quantas janelas estão travando a rolagem ao mesmo tempo (ex.: popup aberto por cima da gaveta do carrinho)
let travas = 0;

/**
 * Largura real da barra de rolagem DA PÁGINA neste momento.
 * É 0 quando a página não rola (ex.: cardápio no desktop, onde só a coluna rola)
 * ou quando o navegador usa barra flutuante (celular, macOS).
 */
function medirBarraDaPagina() {
  return Math.max(0, window.innerWidth - document.documentElement.clientWidth);
}

function aplicarLargura(largura) {
  scrollbarWidth.value = largura;
  document.documentElement.style.setProperty('--scrollbar-width', `${largura}px`);
}

export function useScrollControl() {
  // Recalcula ao redimensionar (com a rolagem travada a barra já sumiu, então mantém a última medida)
  function updateScrollbarWidth() {
    if (!travas) aplicarLargura(medirBarraDaPagina());
  }

  function enableScrollLock() {
    // Mede ANTES de esconder a barra: o body ganha essa margem (scrollbar.css) e o conteúdo não pula
    if (!travas) aplicarLargura(medirBarraDaPagina());
    travas++;
    isScrollLocked.value = true;
  }

  function disableScrollLock() {
    travas = Math.max(0, travas - 1);
    // Só libera quando a última janela fechar
    if (!travas) isScrollLocked.value = false;
  }

  return {
    scrollbarWidth,
    isScrollLocked,
    enableScrollLock,
    disableScrollLock,
    updateScrollbarWidth,
  };
}
