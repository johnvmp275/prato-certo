// src/composables/usePaginaQuery.js

import { ref, watch } from 'vue';

import { useRoute, useRouter } from 'vue-router';

/**
 * Página atual da listagem ligada ao ?page= da URL.
 * - Trocar de página grava na URL (entra no histórico: o "voltar" do navegador volta a página)
 * - F5 e links compartilhados abrem na mesma página
 * - A página 1 não aparece na URL
 */
export function usePaginaQuery() {
  const route = useRoute();
  const router = useRouter();

  // Só reage à URL desta tela (ao sair para outra rota, a query muda antes de a tela ser desmontada)
  const caminho = route.path;

  function lerDaUrl() {
    const numero = Number(route.query.page);
    return Number.isInteger(numero) && numero > 1 ? numero : 1;
  }

  const pagina = ref(lerDaUrl());

  // Página mudou na tela (paginação ou filtros voltando para a 1): atualiza a URL
  watch(pagina, valor => {
    if (route.path !== caminho || valor === lerDaUrl()) return;
    const query = { ...route.query };
    if (valor > 1) query.page = String(valor);
    else delete query.page;
    router.push({ query });
  });

  // URL mudou (voltar/avançar do navegador): atualiza a página da tela
  watch(
    () => route.query.page,
    () => {
      if (route.path !== caminho) return;
      const valor = lerDaUrl();
      if (valor !== pagina.value) pagina.value = valor;
    },
  );

  // Página da URL maior que o total (ex.: ?page=9 depois de excluir registros): vai para a última
  function ajustarAoTotal(quantidadePaginas) {
    if (quantidadePaginas >= 1 && pagina.value > quantidadePaginas) pagina.value = quantidadePaginas;
  }

  return { pagina, ajustarAoTotal };
}
