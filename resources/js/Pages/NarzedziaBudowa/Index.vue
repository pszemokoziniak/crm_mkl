<template>
  <div>
    <Head title="Narzędzia" />
    <BudMenu :budId="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">Narzędzia na budowie</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
      </search-filter-no-filtr>
      <Link class="btn-indigo" :href="`/budowy/${organization.id}/narzedzia/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Numer seryjny</th>
          <th class="pb-4 pt-6 px-6">Ważność badań</th>
          <th class="pb-4 pt-6 px-6 text-center">Ilość</th>
          <th class="pb-4 pt-6 px-6 text-right">Akcje</th>
        </tr>
        <tr v-for="item in toolsOnBuild.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <template v-if="item.narzedzia">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`">
                {{ item.narzedzia.name }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`">
                {{ item.narzedzia.numer_seryjny }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`">
                {{ item.narzedzia.waznosc_badan }}
              </Link>
            </td>
            <td class="border-t text-center">
              <Link class="flex items-center justify-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/narzedzia/${item.id}/edit`">
                {{ item.narzedzia_nb }}
              </Link>
            </td>
            <td class="border-t text-right px-6">
              <delete-button
                :href="`/budowy/${organization.id}/narzedzia/${item.id}/destroy`"
                confirm="Czy na pewno chcesz usunąć to narzędzie z budowy?"
              />
            </td>
          </template>
          <template v-else>
            <td colspan="4" class="border-t px-6 py-4 text-red-500 italic">
              Błąd: Narzędzie o ID {{ item.narzedzia_id }} nie istnieje w bazie danych.
            </td>
            <td class="border-t text-right px-6">
              <delete-button
                :href="`/budowy/${organization.id}/narzedzia/${item.id}/destroy`"
                confirm="To narzędzie nie istnieje w bazie. Czy usunąć ten błędny wpis z budowy?"
              />
            </td>
          </template>
        </tr>
        <tr v-if="toolsOnBuild.data.length === 0">
          <td class="px-6 py-4 border-t text-center text-gray-500" colspan="5">Nie znaleziono narzędzi</td>
        </tr>
      </table>
    </div>
    <pagination class="mt-6" :links="toolsOnBuild.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon.vue'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import BudMenu from '@/Shared/BudMenu.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'
import Pagination from '@/Shared/Pagination.vue'


export default {
  components: {
    BudMenu,
    Head,
    Icon,
    Link,
    DeleteButton,
    SearchFilterNoFiltr,
    Pagination,
  },
  layout: Layout,
  props: {
    toolsOnBuild: Object,
    organization: Object,
    filters: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get(`/budowy/${this.organization.id}/narzedzia`, pickBy(this.form), { preserveState: true })
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
