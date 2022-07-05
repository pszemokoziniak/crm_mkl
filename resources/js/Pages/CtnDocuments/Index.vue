<template>
  <div>
    <Head title="Contacts" />
    <h1 class="mb-8 text-3xl font-bold">Dokumenty</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Trashed:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null" />
          <option value="with">With Trashed</option>
          <option value="only">Only Trashed</option>
        </select>
      </search-filter>
      <Link class="btn-indigo" :href="`/contacts/${contactId}/documents/create`">
        <span>Dodaj</span>
        <span class="hidden md:inline">&nbsp;dokument</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Plik</th>
          <th class="pb-4 pt-6 px-6">Akcje</th>
        </tr>
        <tr v-for="document in documents.data" :key="document.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t" @click="download(document.path)">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500">{{ document.name }} </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" tabindex="-1">{{ document.filename }}</Link>
          </td>
          <td class="border-t">
            <a target="_blank" :href="'/contacts/' + contactId + '/documents/'+ document.id" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
              <DocumentDownloadIcon class="h-5 w-5 text-blue-500" />
              <span>Pobierz</span>
            </a>
          </td>
        </tr>
        <tr v-if="documents.data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono dokumentów</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilter from '@/Shared/SearchFilter'
import { DocumentDownloadIcon } from '@heroicons/vue/solid'

export default {
  components: {
    Head,
    Link,
    SearchFilter,
    DocumentDownloadIcon
  },
  layout: Layout,
  props: {
    contactId: Number,
    filters: Object,
    contact: Object,
    documents: Object,
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
