<template>
  <div>
    <Head title="Dashboard" />
    <h1 class="mb-8 text-3xl font-bold">Dashboard</h1>

    <div v-if="expiring_items.length > 0" class="mb-8">
      <div class="flex items-center mb-4">
        <icon name="eligibility" class="mr-2 w-5 h-5 fill-red-600" />
        <h2 class="text-xl font-bold text-red-600">Kończące się terminy (najbliższe 30 dni)</h2>
      </div>
      <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full whitespace-nowrap">
          <thead>
            <tr class="text-left font-bold bg-red-50">
              <th class="pb-4 pt-6 px-6">Pracownik</th>
              <th class="pb-4 pt-6 px-6">Kategoria</th>
              <th class="pb-4 pt-6 px-6">Rodzaj / Typ</th>
              <th class="pb-4 pt-6 px-6">Data końcowa</th>
              <th class="pb-4 pt-6 px-6">Obecna budowa</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in expiring_items" :key="index" class="hover:bg-gray-100 focus-within:bg-gray-100">
              <td class="border-t">
                <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
                  {{ item.contact.first_name }} {{ item.contact.last_name }}
                </Link>
              </td>
              <td class="border-t px-6 py-4">
                <span class="px-2 py-1 rounded text-xs font-bold bg-gray-200 text-gray-800">{{ item.category }}</span>
              </td>
              <td class="border-t px-6 py-4">
                {{ item.type }}
              </td>
              <td class="border-t px-6 py-4 font-bold text-red-600">
                {{ item.end }}
              </td>
              <td class="border-t">
                <Link v-if="item.organization" class="flex items-center px-6 py-4 focus:text-indigo-500" :href="user_owner[1] === 3 ? `/building/${item.organization.id}/time-sheet` : `/budowy/${item.organization.id}/edit`">
                  {{ item.organization.nazwaBud }}
                </Link>
                <span v-else class="px-6 py-4 text-gray-400 italic">Brak przypisanej budowy</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null">Aktywne budowy</option>
          <option value="my">Moje budowy</option>
          <option value="with">Wszystkie budowy</option>
          <option value="only">Usunięte budowy</option>
        </select>
      </search-filter>
    </div>
    <div v-if="user_owner[1]===3" class="my-3 font-bold mb-3">Twoje budowy</div>
    <div v-if="user_owner[1]===3" class="bg-white rounded-md shadow overflow-x-auto my-3">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
            <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in organizations_user" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/building/${item.id}/time-sheet`">
                {{ item.nazwaBud }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                {{ item.workers_count }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                <div v-if="item.inzynier_name">
                  {{ item.inzynier_name }}
                </div>
                <div v-else-if="item.inzynier">
                  {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                <div v-if="item.country">
                  {{ item.country.name }}
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/building/${item.id}/time-sheet`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="organizations_user.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="py-3 font-bold">Wszystkie aktywne budowy</div>
    <div v-if="user_owner[1]===1 || user_owner[1]===2" class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Ilość Pracowników</th>
            <th class="pb-4 pt-6 px-6">Inżynier budowy</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Kraj</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in organizations_biuro" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${item.id}/edit`">
                {{ item.nazwaBud }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                {{ item.workers_count }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.inzynier_name">
                  {{ item.inzynier_name }}
                </div>
                <div v-else-if="item.inzynier">
                  {{ item.inzynier.first_name }} {{ item.inzynier.last_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <div v-if="item.country">
                  {{ item.country.name }}
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/budowy/${item.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="organizations_biuro.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilter from '@/Shared/SearchFilter'

export default {
  components: {
    Head,
    Icon,
    Link,
    SearchFilter,
  },
  layout: Layout,
  props: {
    filters: Object,
    expiring_items: Array,
    organizations_user: Object,
    organizations_biuro: Object,
    buildings: Object,
    inzynier: Object,
    user_owner: Array,
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
        this.$inertia.get('/', pickBy(this.form), { preserveState: true })
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
