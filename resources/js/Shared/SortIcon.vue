<template>
  <span class="inline-block ml-2">
    <svg v-if="active && direction === 'asc'" class="inline w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
    </svg>

    <svg v-else-if="active && direction === 'desc'" class="inline w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </span>
</template>

<script>
export default {
  props: {
    column: String,
    sort: String,
    direction: String,
  },
  computed: {
    active() {
      return this.column === this.sort
    },
  },
}
</script>
