<template>
  <div class="max-w my-5 bg-white rounded-md shadow overflow-hidden">
    <div class="flex items-center justify-between p-4">
      <h3 class="text-xl font-medium">Dostępni pracownicy</h3>
      <search-filter-no-filtr v-model="search" class="w-full max-w-md" @reset="reset" />
    </div>
    <form @submit.prevent="store()">
      <table class="w-full whitespace-nowrap">
        <tr class="naglowek-tabeli">
          <th>Nazwisko Imię</th>
          <th>Pozycja</th>
          <th>Status</th>
          <!-- Limit 183 dni w kraju tej budowy. Pokazujemy tylko za granicą,
               bo w kraju macierzystym nic nie biegnie. -->
          <th v-if="krajBudowy" class="whitespace-nowrap">Limit {{ krajBudowy }}</th>
          <th class="pb-4 pt-6 px-6">Telefon</th>
        </tr>
        <!-- Kliknięcie w dowolne miejsce wiersza zaznacza/odznacza pracownika. -->
        <tr
          v-for="free in paginatedContacts"
          :key="free.id"
          class="cursor-pointer hover:bg-gray-100 focus-within:bg-gray-100"
          :class="{ 'bg-indigo-50': form.checkedValues.includes(free.id) }"
          @click="przelacz(free.id)"
        >
          <td class="border-t">
            <input class="ml-2 mr-2" type="checkbox" :value="free.id" v-model="form.checkedValues" @click.stop />
            {{ free.last_name }} {{ free.first_name }}
            <icon v-if="free.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </td>
          <td class="border-t px-6 py-4">
            {{ free.fn_name }}
          </td>
          <td class="border-t px-6 py-4">
            {{ free.status_zatrudnienia }}
          </td>
          <td v-if="krajBudowy" class="border-t px-6 py-4 whitespace-nowrap">
            <span v-if="free.limit_183" :class="klasaLimitu(free.limit_183.status)">
              zostało {{ free.limit_183.pozostalo }} dni
            </span>
            <span v-else class="text-gray-300">—</span>
          </td>
          <td class="border-t px-6 py-4">
            {{ free.phone }}
          </td>
        </tr>
        <tr v-if="filteredContactsFree.length === 0">
          <td class="px-6 py-4 border-t" :colspan="krajBudowy ? 5 : 4">Nie znaleziono pracownika</td>
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
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr'
// import mapValues from 'lodash/mapValues'

export default {
  components: {
    Icon,
    LoadingButton,
    SearchFilterNoFiltr,
  },
  layout: Layout,
  props: {
    contacts: Object,
    contactsFree: Object,
    specialists: Object,
    organization: Object,
    krajBudowy: { type: String, default: null },
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
        checkedValues: [],
        start: this.start,
        end: this.end,
      }),
    }
  },
  computed: {
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
    klasaLimitu(status) {
      if (status === 'wyczerpany') return 'font-semibold text-red-700'
      return status === 'uwaga' ? 'font-semibold text-orange-700' : 'text-gray-600'
    },
    przelacz(id) {
      const i = this.form.checkedValues.indexOf(id)
      if (i === -1) {
        this.form.checkedValues.push(id)
      } else {
        this.form.checkedValues.splice(i, 1)
      }
    },
    store() {
      this.form.post(`/pracownicy/${this.organization.id}/`, {
        onSuccess: () => {
          this.form.reset('checkedValues')
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
