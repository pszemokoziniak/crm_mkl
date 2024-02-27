<template>
  <div>
    <Head title="Raporty" />
    <h1 class="mb-8 text-3xl font-bold">Raport miesięczny</h1>
    <div class="flex items-center justify-between mb-6">
      <select-input v-model="date" class="pb-8 pr-6 w-full lg:w-1/5" label="Miesiąc">
        <option v-for="month in months" :key="month" :value="month">{{ month }}</option>
      </select-input>
    </div>
    <a target="_self" :href="`/building/time-sheet/general-report?date=${date.replace('/', '-')}`" class="btn-indigo py-2 px-4 rounded inline-flex items-center">
      <DocumentDownloadIcon class="h-5 w-5 text-blue-500" />
      <span>Pobierz</span>
    </a>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import {DocumentDownloadIcon} from '@heroicons/vue/solid'
import SelectInput from '@/Shared/SelectInput.vue'
import moment from 'moment'

/**
 * Generating months options for select to generate month report
 *
 * @type {string[]}
 */
const months = moment.months()
const formatted = months.map((month) => moment().month(month).format('M'))

const generateFromYear = 2023
let comparable = moment()
const forYears = new Set()

while (comparable.year() >= generateFromYear) {
  forYears.add(comparable.year())
  comparable = comparable.subtract(1, 'y')
}

let select_dates = []
forYears.forEach(function (year) {
  let generated_year_month = formatted
    .filter((month) => !(year === moment().year() && Number.parseInt(month) >= moment().month() + 2))
    .map((month) => `${year}-${month}`)

  select_dates = [...generated_year_month, ...select_dates]
})

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
      date: select_dates.at(-1),
      months: select_dates
    }
  },
}
</script>