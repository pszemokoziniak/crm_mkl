<template>
  <div :class="$attrs.class">
    <label v-if="label" class="form-label" :for="id">{{ label }}:
    </label>
    <select :id="id" ref="input" v-model="selected" v-bind="{ ...$attrs, class: null }" class="form-select" :class="{ error: error }">
      <slot />
    </select>
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
        return `select-input-${uuid()}`
      },
    },
    error: String,
    label: String,
    modelValue: [String, Number, Boolean],
  },
  emits: ['update:modelValue'],
  data() {
    return {
      selected: this.modelValue,
    }
  },
  watch: {
    selected(selected) {
      this.$emit('update:modelValue', selected)
    },
    // Bez tego pole nie nadążało za rodzicem: wyczyszczenie wyboru z zewnątrz
    // zmieniało dane formularza, ale na ekranie zostawała stara pozycja.
    modelValue(wartosc) {
      this.selected = wartosc
    },
  },
  methods: {
    focus() {
      this.$refs.input.focus()
    },
    select() {
      this.$refs.input.select()
    },
  },
}
</script>
