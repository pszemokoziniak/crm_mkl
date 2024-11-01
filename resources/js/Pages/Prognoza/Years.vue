<template>
  <div class="mr-2">
    <div class="bg-white rounded-md shadow p-0 m-0">
      <div class="flex items-center justify-between w-full">
        <div class="bg-white rounded-md shadow flex flex-col p-3 w-full">
          <select-input v-model="selectedYear" class="pr-6 w-full font-bold" label="Wybierz rok">
<!--            <option :value="null">Wszystko</option>-->
            <option v-for="item in data" :key="item" :value="item">{{ item }}</option>
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
    yearSelected: Number ?? null,
  },
  data() {
    return {
      selectedYear: this.yearSelected ?? null,
      // selectedYear: newValue ?? null,
    }
  },
  watch: {
    selectedYear(newValue) {
      this.updateUrl(newValue)
    },
  },
  methods: {
    // buildHref(year) {
    //   const url = new URL(window.location.href)
    //   url.searchParams.set('year', year)
    //   url.searchParams.delete('month')
    //   return url.pathname + url.search
    // },
    // handleSelect() {
    //   const url = new URL(window.location.href)
    //   url.searchParams.delete('year')
    //   url.searchParams.delete('month')
    //   window.location = url
    // },
    updateUrl(selectedValue) {
      const params = new URLSearchParams(window.location.search)
      params.set('year', selectedValue)

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
    reset() {
      const params = new URLSearchParams(window.location.search)
      params.delete('year')

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
  },
}
</script>
