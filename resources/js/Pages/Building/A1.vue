<template>
  <Head title="A1" />
  <BudMenu :bud-id="build" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/organizations">Budowa</Link>
    <span class="text-indigo-400 font-medium">/</span>
    {{ buildDetails.nazwaBud }}
  </h1>
  <h1 class="mb-8 text-3xl font-bold">A1</h1>
  <div class="flex items-center justify-between mb-6">
    <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
    </search-filter-no-filtr>
  </div>
  <div class="bg-white rounded-md shadow overflow-x-auto">
    <table class="w-full whitespace-nowrap">
      <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
        <th class="pb-4 pt-6 px-6">Początek A1</th>
        <th class="pb-4 pt-6 px-6">Koniec A1</th>
        <th class="pb-4 pt-6 px-6">Kraj</th>
      </tr>
      <tr v-for="worker in workers" :key="worker.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${worker.id}/edit`">
            {{ worker.last_name }} {{ worker.first_name }}
          </Link>
        </td>
        <td class="border-t">
          <div class="flex items-center px-6 py-4">
            {{ worker.latest_a1 ? worker.latest_a1.start : '-' }}
          </div>
        </td>
        <td class="border-t">
          <div class="flex items-center px-6 py-4">
            {{ worker.latest_a1 ? worker.latest_a1.end : '-' }}
          </div>
        </td>
        <td class="border-t">
          <div class="flex items-center px-6 py-4">
            {{ worker.latest_a1 && worker.latest_a1.kraj ? worker.latest_a1.kraj.name : '-' }}
          </div>
        </td>
      </tr>
      <tr v-if="workers.length === 0">
        <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracowników</td>
      </tr>
    </table>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'
import mapValues from 'lodash/mapValues'

export default {
  components: {
    Head,
    Link,
    BudMenu,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    build: Number,
    buildDetails: Object,
    workers: Array,
    filters: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get(`/budowy/${this.build}/a1`, pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
