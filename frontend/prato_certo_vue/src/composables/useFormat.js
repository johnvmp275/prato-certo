// src/composables/useFormat.js

const MOEDA = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

/** Formata um número como moeda brasileira (R$ 1.234,56) */
export function formatarMoeda(valor) {
  return MOEDA.format(Number(valor) || 0);
}

/** Converte "AAAA-MM-DD HH:MM:SS" em Date sem problemas de fuso */
function paraData(valor) {
  if (!valor) return null;
  const data = new Date(String(valor).replace(' ', 'T'));
  return Number.isNaN(data.getTime()) ? null : data;
}

/** Formata data (09/11/2026) */
export function formatarData(valor) {
  const data = paraData(valor);
  return data ? data.toLocaleDateString('pt-BR') : '-';
}

/** Formata data e hora (09/11/2026 20:35) */
export function formatarDataHora(valor) {
  const data = paraData(valor);
  if (!data) return '-';
  return `${data.toLocaleDateString('pt-BR')} ${data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`;
}

/** Formata somente a hora (20:35) */
export function formatarHora(valor) {
  const data = paraData(valor);
  return data ? data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '-';
}

/** Formata telefone (11) 98765-4321 */
export function formatarTelefone(valor) {
  const numeros = String(valor ?? '').replace(/\D/g, '');
  if (numeros.length === 11) return numeros.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
  if (numeros.length === 10) return numeros.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
  return valor || '-';
}

/** Formata CPF 000.000.000-00 */
export function formatarCpf(valor) {
  const numeros = String(valor ?? '').replace(/\D/g, '');
  return numeros.length === 11 ? numeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '-';
}

/** Retorna as iniciais de um nome (João Vitor -> JV) */
export function iniciais(nome) {
  return String(nome ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(parte => parte[0])
    .join('')
    .toUpperCase();
}

export function useFormat() {
  return {
    formatarMoeda,
    formatarData,
    formatarDataHora,
    formatarHora,
    formatarTelefone,
    formatarCpf,
    iniciais,
  };
}
