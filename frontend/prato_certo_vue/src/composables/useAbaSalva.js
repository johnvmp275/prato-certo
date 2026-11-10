// src/composables/useAbaSalva.js

import { ref, watch } from 'vue';

const PREFIXO = 'prato-certo:aba:';

/**
 * Aba de filtro que continua selecionada depois do F5.
 * - 'session': sessionStorage (dura enquanto a aba do navegador estiver aberta) → cardápio do cliente
 * - 'local':   localStorage (fica salva no navegador) → telas do painel
 * O armazenamento pode não existir (janela anônima, bloqueado): nesse caso a aba só não é lembrada.
 * @param {string} chave  Identificador da tela (ex: 'painel:mesas')
 * @param {*}      padrao Aba usada quando não há nada salvo ou a salva não existe mais
 * @param {'local'|'session'} tipo
 */
export function useAbaSalva(chave, padrao, tipo = 'local') {
  function armazenamento() {
    return tipo === 'session' ? window.sessionStorage : window.localStorage;
  }

  function ler() {
    try {
      const salvo = armazenamento().getItem(PREFIXO + chave);
      return salvo === null ? padrao : JSON.parse(salvo);
    } catch {
      return padrao;
    }
  }

  const aba = ref(ler());

  watch(aba, valor => {
    try {
      // A aba padrão não precisa ficar salva
      if (valor === padrao) armazenamento().removeItem(PREFIXO + chave);
      else armazenamento().setItem(PREFIXO + chave, JSON.stringify(valor));
    } catch {
      // Armazenamento indisponível: segue sem lembrar a aba
    }
  });

  // A aba salva pode ter deixado de existir (ex.: categoria inativada): volta para a padrão
  function garantirValida(valoresPermitidos) {
    if (!valoresPermitidos.includes(aba.value)) aba.value = padrao;
  }

  return { aba, garantirValida };
}
