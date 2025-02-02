<template>
  <div>
    <Head title="Prognoza" />
    <div class="my-6 font-bold text-2xl">
      <h1>Zestwienie liczby pracowników od {{ startDateFormat }} do {{ endDateFormat }} <span v-if="selectedBuild.id !== 'all'">na budowie {{selectedBuild.nazwaBud}}</span> <span v-if="year">w roku {{year}}</span></h1>
      <ChartComponent :chartData="chartData" />
    </div>
    <div class="m-2">
      <div class="m-2 d-flex">
        <div class="flex-component">
          <Buildings :selectedBuild="selectedBuild" :buildings="buildings" />
        </div>
        <div class="flex-component">
          <Years :data="years" :yearSelected="yearSelected" />
        </div>
        <div class="flex-component" v-if="yearSelected">
          <Months :data="months" :monthSelected="monthSelected" />
        </div>
      </div>
    </div>
    <div v-if="month" class="m-10">
      <Link class="btn-indigo px-10" :href="`/prognoza/create?building=${selectedBuild['id']}&year=${year}&month=${month}`">
        <span>Dodaj</span>
        <span class="hidden md:inline">&nbsp;Godziny</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Data</th>
          <th class="pb-4 pt-6 px-6">Nazwa budowy</th>
          <th class="pb-4 pt-6 px-6 col-2">Ilość pracowników</th>
        </tr>
        <tr v-for="item in data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.prognozadates.id }} / {{ item.prognozadates.start }} - {{ item.prognozadates.end }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.organization.nazwaBud }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.workers_count }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import Years from '@/Pages/Prognoza/Years'
import Buildings from '@/Pages/Prognoza/Buildings.vue'
import Months from '@/Pages/Prognoza/Months.vue'
import ChartComponent from '@/Pages/Prognoza/ChartComponent.vue'

export default {
  components: {
    ChartComponent,
    Months,
    Buildings,
    Head,
    Years,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    years: Array,
    yearSelected: Number,
    months: Array,
    monthSelected: Number,
    data: Object,
    buildings: Array,
    selectedBuild: Object,
    chartData: Object,
    startDate: String,
    endDate: String,
    startDateFormat: String,
    endDateFormat: String,
  },
  data() {
    return {
      edit: false,
      year: this.yearSelected,
      month: null,
    }
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search);
    const year = urlParams.get('year')
    const month = urlParams.get('month')
    const building = urlParams.get('building')
    this.edit = year && month && building !== 'all' ? true : false;
    this.year = year
    this.month = month
  },
}
</script>

<style scoped>
.d-flex {
  display: flex;
}

.flex-component {
  flex: 1;
}
</style>
