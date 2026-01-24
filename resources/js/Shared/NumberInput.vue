<template>
  <div :class="$attrs.class">
    <label v-if="label" class="form-label" :for="id">{{ label }}:</label>
    <input
      :id="id"
      ref="input"
      v-bind="{ ...$attrs, class: null }"
      class="form-input"
      :class="{ error: error }"
      type="number"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      @focus="handleFocus"
    />
    <div v-if="error" class="form-error">{{ error }}</div>
  </div>
</template>

<script>
import { v4 as uuid } from 'uuid'

export default {
  inheritAttrs: false,
  props: {
    id: {
      type: String,
      default() {
        return `number-input-${uuid()}`
      },
    },
    error: String,
    label: String,
    modelValue: [String, Number],
  },
  emits: ['update:modelValue'],
  methods: {
    focus() {
      this.$refs.input.focus()
    },
    handleFocus(event) {
      if (this.modelValue === 0) {
        this.$emit('update:modelValue', '')
      }
      event.target.select()
    },
  },
}
</script>
