<template>
  <div class="bg-white rounded-md shadow flex flex-col p-3">
    <select-input v-model="selected" class="pb-8 pr-6 w-full lg:w-1/1" label="Wybierz budowę" @change="handleSelect()">
      <option value="0" disabled>wybierz</option>
      <option v-for="item in buildings" :key="item.id" :value="item.id">{{ item.nazwaBud }}</option>
    </select-input>
  </div>


</template>

<script>
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {SelectInput},
  props: {
    // initialSelected: String,
    years: Array,
    data: Array,
    buildings: Array,
    selectedBuild: Object,
  },
  data() {
    return {
      selected: this.selectedBuild.id ?? 0,
    }
  },
  watch: {
    selected(newSelected) {
      this.updateUrl(newSelected)
    },
  },
  methods: {
    handleSelect() {
      // No need to manually handle the event since v-model already binds selected
    },
    updateUrl(selectedValue) {
      const params = new URLSearchParams(window.location.search)
      params.set('building', selectedValue)

      this.$inertia.visit(`${window.location.pathname}?${params.toString()}`, {
        method: 'get',
        preserveState: false,
        replace: true,
      })
    },
  },
}
</script>
