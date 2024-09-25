<template>
  <div>
    <Head title="A1" />
    <div>
      <WorkerMenu :contactId="contactId" :userOwner="userOwner"/>
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
    </h1>
    <h1 class="mb-8 text-3xl font-bold">A1</h1>
    <div class="flex items-center justify-between mb-6">
      <Link v-if="userOwner !== 3" class="btn-indigo" :href="`/contacts/${contact.id}/a1/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Początek A1</th>
          <th class="pb-4 pt-6 px-6">Koniec A1</th>
        </tr>
        <tr v-for="item in a1s" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/a1/${item.id}/edit`">
              {{ item.start }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/a1/${item.id}/edit`">
              {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/a1/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <!-- <tr v-if="accounts.data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pozycji</td>
        </tr> -->
      </table>
    </div>
    <h1 class="m-4 font-bold">Dodane pliki</h1>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Plik</th>
          <th class="pb-4 pt-6 px-6">Typ</th>
          <th class="pb-4 pt-6 px-6 text-center">Akcje</th>
        </tr>
        <tr v-for="document in documents.data" :key="document.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t" @click="download(document.path)">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500">{{ document.name }} </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" tabindex="-1">{{ document.filename }}</Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" tabindex="-1">{{ document.dokumentytyp.name }}</Link>
          </td>
          <td class="border-t">
            <div class="flex justify-end">
              <div class="text-center px-4 py-2 m-2">
                <a target="_blank" :href="'/contacts/' + contactId + '/documents/'+ document.id" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center">
                  <DocumentDownloadIcon class="h-5 w-5 text-blue-500" />
                  <span>Pobierz</span>
                </a>
              </div>
              <div v-if="userOwner !== 3" class="text-center px-4 py-2 m-2">
                <a class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded inline-flex items-center cursor-pointer" target="_blank" @click="removeDocument(document.id)" >
                  <TrashIcon class="h-5 w-5 text-blue-500" />
                  <span>Usuń</span>
                </a>
              </div>
            </div>
          </td>
        </tr>
        <tr v-if="documents.data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono dokumentów</td>
        </tr>
      </table>
    </div>
    <!-- <pagination class="mt-6" :links="accounts.links" /> -->
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
// import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
// import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import WorkerMenu from '@/Shared/WorkerMenu'
import {DocumentDownloadIcon, TrashIcon} from '@heroicons/vue/solid'


export default {
  components: {
    Head,
    Icon,
    Link,
    WorkerMenu,
    DocumentDownloadIcon,
    TrashIcon
  },
  layout: Layout,
  props: {
    a1s: Object,
    contact: Object,
    documents: Object,
    userOwner: Number,
  },
  mounted: function () {
    // console.log(this.bads)
  },
  data() {
    return {
      contactId: this.contact.id,
      form: {
        // search: this.filters.search,
        // trashed: this.filters.trashed,
      },
    }
  },
  // watch: {
  //   form: {
  //     deep: true,
  //     handler: throttle(function () {
  //       this.$inertia.get('/badania', pickBy(this.form), { preserveState: true })
  //     }, 150),
  //   },
  // },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    removeDocument(documentId) {
      this.$inertia.delete(`/contacts/${this.contactId}/documents/${documentId}/a1`)
    },
  },
}
</script>
