// src/composables/useDevice.js

import { ref, computed } from 'vue';

const hasWindow = typeof window !== 'undefined';
const hasNavigator = typeof navigator !== 'undefined';

const windowWidth = ref(hasWindow ? window.innerWidth : 0);
const windowHeight = ref(hasWindow ? window.innerHeight : 0);

const screenWidth = ref(hasWindow ? window.screen.width : 0);
const screenHeight = ref(hasWindow ? window.screen.height : 0);

const userAgent = hasNavigator ? navigator.userAgent : '';
const platform = hasNavigator ? navigator.userAgentData?.platform || navigator.platform : '';

function updateDevice() {
  if (!hasWindow) return;

  windowWidth.value = window.innerWidth;
  windowHeight.value = window.innerHeight;

  screenWidth.value = window.screen.width;
  screenHeight.value = window.screen.height;
}

// Device
const isMobile = computed(() => windowWidth.value < 768);
const isTablet = computed(() => windowWidth.value >= 768 && windowWidth.value < 1024);
const isDesktop = computed(() => windowWidth.value >= 1024);

const deviceType = computed(() => {
  if (isMobile.value) return 'mobile';
  if (isTablet.value) return 'tablet';
  return 'desktop';
});

// Sistemas
const isAndroid = computed(() => /Android/i.test(userAgent));

const isIPhone = computed(() => /iPhone/i.test(userAgent));

const isIPad = computed(
  () =>
    /iPad/i.test(userAgent) ||
    (hasNavigator && platform === 'MacIntel' && navigator.maxTouchPoints > 1),
);

const isIOS = computed(() => isIPhone.value || isIPad.value);

const isWindows = computed(() => /Win/i.test(platform));

const isMac = computed(() => /Mac/i.test(platform) && !isIPad.value);

const isLinux = computed(() => /Linux/i.test(platform) && !isAndroid.value);

// Navegadores
const isChrome = computed(() => /Chrome/i.test(userAgent) && !/Edg/i.test(userAgent));

const isFirefox = computed(() => /Firefox/i.test(userAgent));

const isSafari = computed(
  () => /Safari/i.test(userAgent) && !/Chrome/i.test(userAgent) && !/Edg/i.test(userAgent),
);

const isEdge = computed(() => /Edg/i.test(userAgent));

// Touch
const isTouch = computed(() =>
  hasWindow && hasNavigator ? 'ontouchstart' in window || navigator.maxTouchPoints > 0 : false,
);

export function useDevice() {
  return {
    // Tamanho
    windowWidth,
    windowHeight,
    screenWidth,
    screenHeight,

    // Tipo
    isMobile,
    isTablet,
    isDesktop,
    deviceType,

    // Sistema
    isAndroid,
    isIPhone,
    isIPad,
    isIOS,
    isWindows,
    isMac,
    isLinux,

    // Navegador
    isChrome,
    isFirefox,
    isSafari,
    isEdge,

    // Outros
    isTouch,
    userAgent,
    platform,

    updateDevice,
  };
}
