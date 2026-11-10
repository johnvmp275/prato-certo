import { defineStore } from 'pinia';

export const useNavigation = defineStore('navigation', {
  state: () => ({
    isOpen: false,
  }),
  actions: {
    openNavigation() {
      this.isOpen = true;
    },
    closeNavigation() {
      this.isOpen = false;
    },
    toggleNavigation() {
      this.isOpen = !this.isOpen;
    },
  },
});
