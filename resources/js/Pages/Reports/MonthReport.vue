<template>
  <div>
    <Head title="Raporty" />
    <h1 class="mb-8 text-3xl font-bold">Raport miesięczny</h1>

    <div class="flex items-center justify-between mb-6">
      <select-input v-model="month" class="pb-8 pr-6 w-full lg:w-1/5" label="Miesiąc">
        <option v-for="month in months" :value="month">{{ month }}</option>
      </select-input>
    </div>
    <a target="_self" :href="`/building/time-sheet/general-report`" class="btn-indigo py-2 px-4 rounded inline-flex items-center">
      <DocumentDownloadIcon class="h-5 w-5 text-blue-500" />
      <span>Pobierz</span>
    </a>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import {DocumentDownloadIcon} from '@heroicons/vue/solid'
import SelectInput from "@/Shared/SelectInput.vue";

export default {
  components: {
    SelectInput,
    DocumentDownloadIcon,
    Head,
  },
  layout: Layout,
  props: {
    months: Array,
  },
  data() {
    return {
      months: [
        '2024-01'
      ]
    }
  }
}
</script>