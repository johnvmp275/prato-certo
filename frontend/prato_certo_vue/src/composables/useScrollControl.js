import { ref } from 'vue';
import { useScrollLock } from '@vueuse/core';

const scrollbarWidth = ref(0);
const isScrollLocked = useScrollLock(document.body);

export function useScrollControl() {
  function updateScrollbarWidth() {
    const tempElement = document.createElement('div');
    tempElement.style.cssText = `
      position: absolute;
      top: -9999px;
      width: 100px;
      height: 100px;
      overflow: scroll;
    `;

    document.body.appendChild(tempElement);
    const width = tempElement.offsetWidth - tempElement.clientWidth;
    document.body.removeChild(tempElement);

    scrollbarWidth.value = width;
    document.documentElement.style.setProperty('--scrollbar-width', `${width}px`);
  }

  function enableScrollLock() {
    isScrollLocked.value = true;
  }

  function disableScrollLock() {
    isScrollLocked.value = false;
  }

  return {
    scrollbarWidth,
    isScrollLocked,
    enableScrollLock,
    disableScrollLock,
    updateScrollbarWidth,
  };
}
