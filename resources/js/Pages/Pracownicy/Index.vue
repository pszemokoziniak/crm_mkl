<template>
  <div>
    <Head title="Budowa" />
    <BudMenu :budId="organization_id" />
    <h1 class="mb-8 text-3xl font-bold">Budowa Pracownicy</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Trashed:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option value="with">Wszystko</option>
          <option value="only">Usunięte</option>
        </select>
      </search-filter-no-filtr>
      <Link class="btn-indigo" :href="`/pracownicy/${organization_id}/create`">
        <span>Dodaj / Usuń</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Czas Pracy</th>
          <th class="pb-4 pt-6 px-6">Stanowisko</th>
        </tr>
        <tr v-for="item in contactworkdates.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              {{ item.contact.last_name }} {{ item.contact.first_name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              od: {{ item.start }}  do: {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              <div v-if="item.contact.funkcja">
                {{ item.contact.funkcja.name }}
              </div>
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="w-px border-t p-2">
            <Link class="flex items-center px-4 mb-4 underline text-indigo-600" tabindex="-1" :href="`/pracownicy/${organization_id}/edit/${item.id}`">
              Popraw daty
            </Link>
            <Link v-if="user_owner !== 3" class="flex items-center px-4 underline text-indigo-600" tabindex="-1" @click="destroy(item.id)">
              Usuń
            </Link>
          </td>
        </tr>
        <tr v-if="contactworkdates === null">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'
import mapValues from 'lodash/mapValues'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'


export default {
  components: {
    SearchFilterNoFiltr,
    Head,
    Icon,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    contactworkdates: Object,
    organization_id: Number,
    filters: Object,
    user_owner: Number,
    // contact_work_dates: Object,
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
        this.$inertia.get('/pracownicy/'+ this.organization_id, pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    destroy(worker) {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/pracownicy/${worker}`)
      }
    },
  },
}
</script>
