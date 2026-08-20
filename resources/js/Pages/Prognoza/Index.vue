<template>
  <div>
    <Head title="Prognoza" />
    <div class="my-6 font-bold text-2xl">
      <h1>Zestawienie liczby pracowników</h1>
      <h2>{{ startDateFormat }} do {{ endDateFormat }} <span v-if="selectedBuild.id !== 'all'">na budowie {{ selectedBuild.nazwaBud }}</span> <span v-if="year">w roku {{ year }}</span></h2>
      <ChartComponent :chartData="chartData" :chartMax="chartMax" />
    </div>
    <div class="m-2">
      <div class="m-2 space-y-3">
        <div class="w-full">
          <Buildings :selectedBuild="selectedBuild" :buildings="buildings" />
        </div>
        <div class="flex gap-3">
          <div class="w-1/2">
            <Years :data="years" :yearSelected="yearSelected" />
          </div>
          <div class="w-1/2" v-if="yearSelected">
            <Months :data="months" :monthSelected="monthSelected" />
          </div>
          <div class="w-1/2" v-else></div>
        </div>
        <!-- Guzik w tym samym kontenerze co filtry, żeby trzymał ich lewą krawędź. -->
        <div v-if="month" class="pt-2">
          <Link class="btn-indigo px-10" :href="`/prognoza/create?building=${selectedBuild['id']}&year=${year}&month=${month}`">
            <span>Dodaj</span>
            <span class="hidden md:inline">&nbsp;Pracowników</span>
          </Link>
        </div>
      </div>
    </div>
    <div class="flex items-center justify-between mb-4 mt-6">
      <search-filter-no-filtr v-model="tableSearch" class="w-full max-w-md" @reset="tableSearch = ''" />
      <span class="ml-4 text-sm text-gray-500 whitespace-nowrap">{{ displayedRows.length }} z {{ data.length }}</span>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6 cursor-pointer select-none hover:text-indigo-600" @click="sortBy('date')">Data <span class="text-gray-400">{{ sortArrow('date') }}</span></th>
          <th class="pb-4 pt-6 px-6 cursor-pointer select-none hover:text-indigo-600" @click="sortBy('name')">Nazwa budowy <span class="text-gray-400">{{ sortArrow('name') }}</span></th>
          <th class="pb-4 pt-6 px-6 col-2 cursor-pointer select-none hover:text-indigo-600" @click="sortBy('count')">Ilość pracowników <span class="text-gray-400">{{ sortArrow('count') }}</span></th>
        </tr>
        <tr v-for="item in displayedRows" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.prognozadates.start }} - {{ item.prognozadates.end }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-4" :href="`/prognoza/${item.id}/edit`" tabindex="-1">
              {{ item.organization?.nazwaBud ?? '— budowa usunięta —' }}
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
        <tr v-if="displayedRows.length === 0">
          <td class="px-6 py-4 border-t text-gray-500" colspan="4">Brak wyników.</td>
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
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'

export default {
  components: {
    ChartComponent,
    SearchFilterNoFiltr,
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
    months: { type: Array, required: true },
    monthSelected: Number,
    data: { type: Array, required: true },
    buildings: Array,
    selectedBuild: Object,
    chartData: Object,
    chartMax: Number,
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
      tableSearch: '',
      sortKey: 'date',
      sortDir: 'asc',
    }
  },
  computed: {
    displayedRows() {
      const q = this.tableSearch.trim().toLowerCase()
      let rows = this.data
      if (q) {
        rows = rows.filter((it) => {
          const range = `${it.prognozadates?.start ?? ''} - ${it.prognozadates?.end ?? ''}`.toLowerCase()
          const name = (it.organization?.nazwaBud ?? '').toLowerCase()
          return range.includes(q) || name.includes(q)
        })
      }
      const dir = this.sortDir === 'asc' ? 1 : -1
      const key = this.sortKey
      return [...rows].sort((a, b) => {
        let av, bv
        if (key === 'name') {
          av = (a.organization?.nazwaBud ?? '').toLowerCase()
          bv = (b.organization?.nazwaBud ?? '').toLowerCase()
        } else if (key === 'count') {
          av = Number(a.workers_count)
          bv = Number(b.workers_count)
        } else {
          av = a.prognozadates?.start ?? ''
          bv = b.prognozadates?.start ?? ''
        }
        if (av < bv) return -1 * dir
        if (av > bv) return 1 * dir
        return 0
      })
    },
  },
  methods: {
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortKey = key
        this.sortDir = 'asc'
      }
    },
    sortArrow(key) {
      if (this.sortKey !== key) return '↕'
      return this.sortDir === 'asc' ? '↑' : '↓'
    },
  },
  mounted() {
    const urlParams = new URLSearchParams(window.location.search)
    const year = urlParams.get('year')
    const month = urlParams.get('month')
    const building = urlParams.get('building')
    this.edit = !!(year && month && building !== 'all')
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
