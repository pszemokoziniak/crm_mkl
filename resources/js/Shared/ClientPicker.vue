<template>
  <div :class="$attrs.class">
    <label v-if="label" class="form-label">{{ label }}:</label>
    <div class="relative">
      <input
        ref="input"
        class="form-input"
        :class="{ error: error }"
        :value="modelValue"
        :disabled="disabled"
        autocomplete="off"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
      />
      <div
        v-if="open && results.length"
        class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded shadow max-h-60 overflow-auto"
      >
        <button
          v-for="c in results"
          :key="c.id"
          type="button"
          class="block w-full text-left px-3 py-2 hover:bg-indigo-50"
          @mousedown.prevent="pick(c)"
        >
          <span class="font-medium">{{ c.nazwa }}</span>
          <span v-if="c.miasto" class="text-gray-500"> — {{ c.miasto }}</span>
        </button>
      </div>
      <div
        v-else-if="open && !loading && q.trim().length >= 2"
        class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded shadow px-3 py-2 text-sm text-gray-500"
      >
        Brak klienta w CRM — możesz wpisać nazwę ręcznie.
      </div>
    </div>
    <p v-if="clientId" class="mt-1 text-xs text-green-700">Powiązano z klientem CRM (#{{ clientId }})</p>
    <div v-if="error" class="form-error">{{ error }}</div>
  </div>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    modelValue: { type: String, default: '' },
    clientId: { type: [Number, String, null], default: null },
    label: String,
    error: String,
    disabled: Boolean,
  },
  emits: ['update:modelValue', 'update:clientId'],
  data() {
    return {
      open: false,
      loading: false,
      results: [],
      q: this.modelValue || '',
      timer: null,
    }
  },
  methods: {
    onFocus() {
      if (this.results.length) this.open = true
    },
    onBlur() {
      // Opóźnienie, żeby kliknięcie w podpowiedź zdążyło się zarejestrować.
      setTimeout(() => { this.open = false }, 150)
    },
    onInput(e) {
      const val = e.target.value
      this.q = val
      this.$emit('update:modelValue', val)
      // Ręczna edycja zrywa powiązanie z klientem CRM.
      this.$emit('update:clientId', null)
      this.debouncedSearch()
    },
    debouncedSearch() {
      clearTimeout(this.timer)
      if (this.q.trim().length < 2) {
        this.results = []
        this.open = false
        return
      }
      this.timer = setTimeout(this.search, 250)
    },
    async search() {
      this.loading = true
      try {
        const res = await fetch(`/crm/klienci?q=${encodeURIComponent(this.q.trim())}`, {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        })
        this.results = res.ok ? await res.json() : []
      } catch (e) {
        this.results = []
      }
      this.loading = false
      this.open = true
    },
    pick(c) {
      this.q = c.nazwa
      this.$emit('update:modelValue', c.nazwa)
      this.$emit('update:clientId', c.id)
      this.results = []
      this.open = false
    },
  },
}
</script>
