<template>
  <div>
    <Head title="Budowa" />
    <h1 class="mb-8 text-3xl font-bold">Budowy</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null">Budowy aktywne</option>
<!--          <option value="with">Wszystkie</option>-->
          <option value="only">Zakończona</option>
        </select>
      </search-filter>
      <Link class="btn-indigo" href="/budowy/create">
        <span>Utwórz</span>
        <span class="hidden md:inline">&nbsp;Budowę</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Numer projektu</th>
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Kraj</th>
            <th class="pb-4 pt-6 px-6">Kierownik</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Inżynier</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="organization in organizations.data" :key="organization.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/edit`">
                {{ organization.numerBud }}
                <icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/edit`">
                {{ organization.nazwaBud }}
                <icon v-if="organization.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
                <div v-if="organization.country">
                  {{ organization.country.name}}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
                <div v-if="organization.kierownikBud_id">
                  {{ organization.kierownikBud_id.last_name }} {{ organization.kierownikBud_id.first_name }}
                </div>
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
                <div v-if="organization.inzynier">
                  {{ organization.inzynier.last_name }} {{ organization.inzynier.first_name }}
                </div>
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="`/budowy/${organization.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
          <tr v-if="organizations.data.length === 0">
            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>
          </tr>
        </tbody>
      </table>
    </div>
    <pagination class="mt-6" :links="organizations.links" />
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
    organizations: Object,
    inzyniers: Object,
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
        this.$inertia.get('/budowy', pickBy(this.form), { preserveState: true })
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
