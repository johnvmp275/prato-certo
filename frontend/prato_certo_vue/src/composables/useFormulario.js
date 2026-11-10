// src/composables/useFormulario.js

import { reactive, ref } from 'vue';

/**
 * Controla os erros e o envio de um formulário de cadastro.
 * O erro retornado pela API é exibido no campo informado em "campo";
 * quando não há campo, aparece como erro geral.
 */
export function useFormulario(campos) {
  const erros = reactive(Object.fromEntries(campos.map(campo => [campo, ''])));
  const erroGeral = ref('');
  const salvando = ref(false);

  function limparErros() {
    Object.keys(erros).forEach(campo => (erros[campo] = ''));
    erroGeral.value = '';
  }

  // Aplica as validações locais: { campo: 'mensagem' | '' }
  function validar(regras) {
    limparErros();
    Object.entries(regras).forEach(([campo, mensagem]) => {
      if (mensagem) erros[campo] = mensagem;
    });
    return Object.values(regras).every(mensagem => !mensagem);
  }

  async function enviar(operacao) {
    salvando.value = true;
    try {
      return await operacao();
    } catch (error) {
      if (error.campo && error.campo in erros) {
        erros[error.campo] = error.mensagem;
      } else {
        erroGeral.value = error.mensagem;
      }
      return null;
    } finally {
      salvando.value = false;
    }
  }

  return {
    erros,
    erroGeral,
    salvando,
    validar,
    enviar,
    limparErros,
  };
}
