<script setup>
defineOptions({
  name: 'CommonMessageError',
});

const props = defineProps({
  error: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <transition name="message-error-animation">
    <span
      v-if="props.error"
      class="message-error"
    >
      <span class="message-error-wrapper">
        <span class="message-error-holder">{{ props.error }}</span>
      </span>
    </span>
  </transition>
</template>

<style scoped>
.message-error {
  display: grid;
  transition: 250ms;
  overflow: hidden;
  color: var(--color-error-600);
}
.message-error-wrapper {
  display: block;
  overflow: hidden;
}
.message-error-holder {
  display: block;
  margin-top: 5px;
  font-size: 12px;
}
.message-error-animation-enter-active,
.message-error-animation-leave-active {
  transition: 250ms;
}
.message-error-animation-enter-from,
.message-error-animation-leave-to {
  grid-template-rows: 0fr;
}
.message-error-animation-enter-to,
.message-error-animation-leave-from {
  grid-template-rows: 1fr;
}
</style>
