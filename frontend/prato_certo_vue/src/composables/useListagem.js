// src/composables/useListagem.js

import { reactive, ref, watch } from 'vue';

import { useToast } from '@/stores/useToast';
import { usePaginaQuery } from '@/composables/usePaginaQuery';

/**
 * Lógica comum das telas de cadastro: busca, filtros, paginação e recarga.
 * @param {Function} buscar   Função do service que recebe os parâmetros
 * @param {string}   chave    Nome da lista na resposta (ex: 'produtos')
 * @param {Object}   filtrosIniciais
 */
export function useListagem(buscar, chave, filtrosIniciais = {}) {
  const toast = useToast();

  const itens = ref([]);
  const paginacao = ref({});
  const carregando = ref(true);
  const busca = ref('');
  // Página ligada ao ?page= da URL (F5, link e "voltar" do navegador mantêm a página)
  const { pagina, ajustarAoTotal } = usePaginaQuery();
  const filtros = reactive({ ...filtrosIniciais });

  async function carregar() {
    carregando.value = true;
    try {
      const resposta = await buscar({ ...filtros, busca: busca.value, page: pagina.value });
      itens.value = resposta[chave];
      paginacao.value = resposta.paginacao ?? {};
      ajustarAoTotal(paginacao.value.quantidadePaginas);
    } catch (error) {
      toast.error(error.mensagem);
    } finally {
      carregando.value = false;
    }
  }

  // Qualquer filtro novo volta para a primeira página
  watch([busca, filtros], () => {
    if (pagina.value !== 1) {
      pagina.value = 1;
    } else {
      carregar();
    }
  });

  watch(pagina, carregar);

  return {
    itens,
    paginacao,
    carregando,
    busca,
    pagina,
    filtros,
    carregar,
  };
}
