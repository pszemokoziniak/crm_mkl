<template>
  <div>
    <Head title="Magazyn Sprzętu" />
    <h1 class="mb-8 text-3xl font-bold text-gray-900">Magazyn Sprzętu</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wyświetlaj:</label>
        <select v-model="form.wyswietlaj" class="form-select mt-1 w-full">
          <option :value="null">Wszystkie</option>
          <option value="dostepne">Dostępne</option>
          <option value="na_budowie">Na budowie</option>
        </select>
      </search-filter>
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
            <th class="py-4 px-6 text-xs uppercase tracking-wider text-gray-500">Na budowach</th>
            <th class="py-4 px-6" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="item in narzedzia.data" :key="item.id" class="hover:bg-gray-50 transition-colors group">
            <td class="px-6 py-4">
              <Link class="flex items-center font-medium text-gray-900 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
                <img v-if="item.photo" :src="item.photo" :alt="item.name" class="flex-shrink-0 mr-3 w-12 h-12 object-cover rounded border border-gray-200" />
                <span v-else class="flex flex-shrink-0 items-center justify-center mr-3 w-12 h-12 bg-gray-50 rounded border border-gray-200">
                  <icon name="sprzet2" class="w-5 h-5 fill-gray-300" />
                </span>
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
            <td class="px-6 py-4">
              <div v-if="item.budowy && item.budowy.length" class="flex flex-col gap-0.5 text-xs leading-tight text-gray-600 max-w-xs">
                <span v-for="(b, i) in item.budowy" :key="i" class="truncate">
                  {{ b.nazwaBud }}<span v-if="b.qty > 1" class="text-gray-400"> ({{ b.qty }})</span>
                </span>
              </div>
              <span v-else class="text-gray-300 text-xs">-</span>
            </td>
            <td class="px-6 py-4 text-right">
              <Link :href="`/narzedzia/${item.id}/edit`" class="text-gray-400 group-hover:text-indigo-600 transition-colors">
                <icon name="cheveron-right" class="w-6 h-6 fill-current" />
              </Link>
            </td>
          </tr>
          <tr v-if="narzedzia.data.length === 0">
            <td class="px-6 py-12 text-center text-gray-500" colspan="7">
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
import SearchFilter from '@/Shared/SearchFilter.vue'
import Pagination from '@/Shared/Pagination.vue'


export default {
  components: {
    SearchFilter,
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
        wyswietlaj: this.filters.wyswietlaj,
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
