<template>
  <div>
    <div class="bg-white rounded-md shadow p-0 m-0">
      <div class="flex items-center justify-between w-full">
        <div class="bg-white rounded-md shadow flex flex-col p-3 w-full">
          <select-input v-model="selected" class="pr-6 w-full font-bold" label="Wybierz miesiąc" @change="handleChange">
            <option v-for="(item, index) in data" :key="index" :value="index">{{ item }}</option>
          </select-input>
        </div>
        <button class="p-3 text-gray-500 hover:text-gray-700 focus:text-indigo-500 text-sm" type="button" @click="reset">Wyczyść</button>
      </div>
    </div>
  </div>
</template>

<script>
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {
    SelectInput,
  },
  props: {
    data: Array,
    monthSelected: Number,
  },
  data() {
    return {
      selected: this.monthSelected ?? null,
    }
  },
  watch: {
    selected(newSelected) {
      this.updateUrl(newSelected)
    },
  },
  methods: {
    updateUrl(selectedValue) {
      const params = new URLSearchParams(window.location.search)
      params.set('month', selectedValue)

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
    reset() {
      const params = new URLSearchParams(window.location.search)
      params.delete('month')

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
  },
}
</script>
