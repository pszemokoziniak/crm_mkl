<template>
  <div class="max-w my-5 bg-white rounded-md shadow overflow-hidden">
    <h3 class="p-4 text-xl font-medium">Przypisz kierownika i inżyniera</h3>

    <div class="grid gap-4 grid-cols-1 p-4 md:grid-cols-2">
      <div>
        <label class="block mb-1 text-sm font-medium">Kierownik</label>
        <select v-model="form.manager_id" class="form-select w-full">
          <option :value="null">— wybierz —</option>
          <option v-for="p in managers" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }} ({{ p.fn_name }})</option>
        </select>
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium">Inżynier</label>
        <select v-model="form.engineer_id" class="form-select w-full">
          <option :value="null">— wybierz —</option>
          <option v-for="p in engineers" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }} ({{ p.fn_name }})</option>
        </select>
      </div>
    </div>
    <div class="flex items-center justify-between p-4">
      <h3 class="text-xl font-medium">Dostępni pracownicy</h3>
      <search-filter-no-filtr v-model="search" class="w-full max-w-md" @reset="reset" />
    </div>
    <form @submit.prevent="store()">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Pozycja</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
        </tr>
        <tr v-for="free in paginatedContacts" :key="free.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <input class="ml-2 mr-2" type="checkbox" :value="free.id" v-model="form.checkedValues" />
            {{ free.last_name }} {{ free.first_name }}
            <icon v-if="free.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.fn_name }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.phone }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="filteredContactsFree.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
      <div v-if="filteredContactsFree.length > pageSize" class="flex justify-center py-4">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(page, index) in totalPages" :key="index">
            <button
              type="button"
              class="mb-1 mr-1 px-4 py-3 focus:text-indigo-500 text-sm leading-4 hover:bg-white border focus:border-indigo-500 rounded"
              :class="{ 'bg-white': currentPage === page }"
              @click="currentPage = page"
            >
              {{ page }}
            </button>
          </template>
        </div>
      </div>
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj pracowników</loading-button>
      </div>
    </form>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr'
// import mapValues from 'lodash/mapValues'

export default {
  components: {
    Icon,
    LoadingButton,
    Link,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    contacts: Object,
    contactsFree: Object,
    specialists: Object,
    organization: Object,
    start: String,
    end: String,
  },
  remember: 'form',
  data() {
    return {
      search: '',
      currentPage: 1,
      pageSize: 20,
      form: this.$inertia.form({
        manager_id: null,
        engineer_id: null,
        checkedValues: [],
        start: this.start,
        end: this.end,
      }),
    }
  },
  computed: {
    managers() {
      return (this.specialists || []).filter((x) => x.funkcja_id == 1)
    },
    engineers() {
      return (this.specialists || []).filter((x) => x.funkcja_id == 6)
    },
    filteredContactsFree() {
      let result = this.contactsFree || []
      if (this.search) {
        const lowerSearch = this.search.toLowerCase()
        result = result.filter((contact) => {
          const fullName = `${contact.last_name} ${contact.first_name}`.toLowerCase()
          const position = (contact.fn_name || '').toLowerCase()
          return fullName.includes(lowerSearch) || position.includes(lowerSearch)
        })
      }
      return result
    },
    totalPages() {
      return Math.ceil(this.filteredContactsFree.length / this.pageSize)
    },
    paginatedContacts() {
      const start = (this.currentPage - 1) * this.pageSize
      const end = start + this.pageSize
      return this.filteredContactsFree.slice(start, end)
    },
  },
  watch: {
    search() {
      this.currentPage = 1
    },
  },
  methods: {
    store() {
      this.form.post(`/pracownicy/${this.organization.id}/`, {
        onSuccess: () => {
          this.form.reset('checkedValues', 'manager_id', 'engineer_id')
          this.search = ''
        },
      })
    },
    reset() {
      this.search = ''
    },
  },
}
</script>
