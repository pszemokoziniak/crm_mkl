<template>
  <div class="flex items-center gap-2">
    <button type="button" class="px-2 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" aria-label="Poprzedni miesiąc" @click="przesun(-1)">‹</button>
    <input :value="modelValue" type="month" class="form-input py-1 text-sm" @change="$emit('update:modelValue', $event.target.value)" />
    <button type="button" class="px-2 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" aria-label="Następny miesiąc" @click="przesun(1)">›</button>
  </div>
</template>

<script>
export default {
  props: { modelValue: { type: String, required: true } },
  emits: ['update:modelValue'],
  methods: {
    przesun(delta) {
      const [r, m] = this.modelValue.split('-').map(Number)
      const d = new Date(r, m - 1 + delta, 1)
      this.$emit('update:modelValue', `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
    },
  },
}
</script>
