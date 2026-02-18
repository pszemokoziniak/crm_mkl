<template>
  <div>
    <Head title="Magazyn Sprzętu" />
    <h1 class="mb-8 text-3xl font-bold text-gray-900">Magazyn Sprzętu</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset" />
      <Link class="btn-indigo" href="/narzedzia/create">
        <span>Dodaj nowy sprzęt</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold bg-gray-50 border-b border-gray-100">
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Nazwa</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Numer Seryjny</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Wszystkie</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">Na budowie</th>
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500 text-center">W magazynie</th>
            <th class="py-4 px-6" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in narzedzia.data" :key="item.id" class="hover:bg-gray-50 transition-colors group">
            <td class="px-6 py-4">
              <Link class="flex items-center font-medium text-gray-900 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
                {{ item.name }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="px-6 py-4 text-gray-600">
              {{ item.numer_seryjny || '-' }}
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                {{ item.ilosc_all ?? 0 }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <span v-if="(item.ilosc_budowa ?? 0) > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                {{ item.ilosc_budowa }}
              </span>
              <span v-else class="text-gray-300 text-xs">-</span>
            </td>
            <td class="px-6 py-4 text-center">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                  (item.ilosc_magazyn ?? 0) > 0 ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'
                ]"
              >
                {{ item.ilosc_magazyn ?? 0 }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <Link :href="`/narzedzia/${item.id}/edit`" class="text-gray-400 group-hover:text-indigo-600 transition-colors">
                <icon name="cheveron-right" class="w-6 h-6 fill-current" />
              </Link>
            </td>
          </tr>
          <tr v-if="narzedzia.data.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="6">
              <div class="flex flex-col items-center">
                <icon name="office" class="w-12 h-12 fill-gray-200 mb-2" />
                <p>Nie znaleziono żadnego sprzętu w magazynie</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination class="mt-6" :links="narzedzia.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'
import Pagination from '@/Shared/Pagination.vue'


export default {
  components: {
    SearchFilterNoFiltr,
    Pagination,
    Head,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    filters: Object,
    narzedzia: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/narzedzia', pickBy(this.form), { preserveState: true })
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
