<template>
  <div>
    <Head title="Kraje" />
    <h1 class="mb-8 text-3xl font-bold">Sprzęt</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
<!--        <label class="block text-gray-700">Trashed:</label>-->
<!--        <select v-model="form.trashed" class="form-select mt-1 w-full">-->
<!--          &lt;!&ndash;          <option :value="null" />&ndash;&gt;-->
<!--          <option value="with">Wszystko</option>-->
<!--          <option value="only">Usunięte</option>-->
<!--        </select>-->
      </search-filter-no-filtr>
      <Link class="btn-indigo" href="/narzedzia/create">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Numer Seryjny</th>
          <th class="pb-4 pt-6 px-6">Ilość Całkowita</th>
          <th class="pb-4 pt-6 px-6">Ilość Budowa</th>
          <th class="pb-4 pt-6 px-6">Ilość Magazyn</th>
        </tr>
        <tr v-for="item in narzedzia" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
              {{ item.name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
              {{ item.numer_seryjny }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
              {{ item.ilosc_all }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
              {{ item.ilosc_budowa }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.id}/edit`">
              {{ item.ilosc_magazyn }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/narzedzia/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="narzedzia.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pozycji</td>
        </tr>
      </table>
    </div>
    <!-- <pagination class="mt-6" :links="accounts.links" /> -->
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


export default {
  components: {
    SearchFilterNoFiltr,
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
