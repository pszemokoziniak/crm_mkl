<template>
  <button
    type="button"
    class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-sm font-medium rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
    @click="handleClick"
  >
    <icon name="trash" class="w-3 h-3 mr-1.5 fill-red-700" />
    <slot>Usuń</slot>
  </button>
</template>

<script>
import Icon from '@/Shared/Icon'

export default {
  components: {
    Icon,
  },
  props: {
    href: String,
    confirm: {
      type: String,
      default: 'Czy na pewno chcesz to usunąć?',
    },
  },
  methods: {
    handleClick() {
      if (confirm(this.confirm)) {
        this.$inertia.delete(this.href)
      }
    },
  },
}
</script>
