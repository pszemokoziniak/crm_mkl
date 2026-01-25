<template>
  <div>
    <Head title="Contacts" />
    <h1 class="mb-8 text-3xl font-bold">Pracownicy</h1>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
      <div class="flex items-center w-full sm:w-auto">
        <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
          <label class="block text-gray-700">Wybierz:</label>
          <select v-model="form.trashed" class="form-select mt-1 w-full">
            <option value="with">Wszystkie</option>
            <option value="only">Usunięte</option>
          </select>
        </search-filter>
      </div>
      <Link class="btn-indigo w-full sm:w-auto text-center" href="/contacts/create">
        <span>Dodaj</span>
        <span class="hidden md:inline">&nbsp;Pracownika</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left font-bold border-b">
            <th class="pb-4 pt-6 px-4">Nazwisko Imię</th>
            <th class="pb-4 pt-6 px-4">Stanowisko</th>
            <th class="pb-4 pt-6 px-4">Pracuje na budowie</th>
            <th class="pb-4 pt-6 px-4">Status</th>
            <th class="pb-4 pt-6 px-4" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="contact in contacts.data" :key="contact.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t whitespace-nowrap">
              <Link class="flex items-center px-4 py-3 focus:text-indigo-500 font-medium" :href="`/contacts/${contact.id}/edit`">
                {{ contact.last_name }} {{ contact.name }}
                <icon v-if="contact.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-4 py-3" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
                <div v-if="contact.funkcja">
                  {{ contact.funkcja.name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-4 py-3" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
                <div v-if="contact.pracuje" class="max-w-[300px]">
                  {{ contact.pracuje }}
                </div>
              </Link>
            </td>
            <td class="border-t whitespace-nowrap">
              <Link class="flex items-center px-4 py-3" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
                <div v-if="contact.deleted_at">
                  <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded bg-red-100 text-red-800">
                    Nieaktywny
                  </span>
                </div>
                <div v-else-if="contact.status_zatrudnienia">
                  <span
                    class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded"
                    :class="contact.status_zatrudnienia === 'Aktywny' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ contact.status_zatrudnienia }}
                  </span>
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/contacts/${contact.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-5 h-5 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="contacts.data.length === 0">
            <td class="px-6 py-4 border-t" colspan="5">Nie znaleziono kontaktu</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination class="mt-6" :links="contacts.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import Pagination from '@/Shared/Pagination'
import SearchFilter from '@/Shared/SearchFilter'

export default {
  components: {
    Head,
    Icon,
    Link,
    Pagination,
    SearchFilter,
  },
  layout: Layout,
  props: {
    filters: Object,
    contactAccount: Object,
    contacts: Object,
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
        this.$inertia.get('/contacts', pickBy(this.form), { preserveState: true })
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
