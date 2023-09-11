<template>
  <div>
    <Head title="Narzędzia" />
    <h1 class="mb-8 text-3xl font-bold">Narzędzia na budowie</h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/budowy/${organization.id}/narzedzia/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Daty</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Ilość</th>
        </tr>
        <tr v-for="item in toolsOnBuild.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/budowy/${organization.id}/edit/${item.id}`">
              {{ item.narzedzia.name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit/${item.id}`" tabindex="-1">
              od: {{ item.start }}  do: {{ item.end }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/budowy/${organization.id}/edit/${item.id}`" tabindex="-1">
              {{ item.narzedzia_nb }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/budowy/${organization.id}/edit/${item.id}`" tabindex="-1">
              <icon name="destroy" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="toolsFree === null">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
    <!-- <pagination class="mt-6" :links="accounts.links" /> -->
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon.vue'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'


export default {
  components: {
    Head,
    Icon,
    Link,
  },
  layout: Layout,
  props: {
    toolsOnBuild: Object,
    organization: Object,
  },
  data() {
    return {
      form: {
        // search: this.filters.search,
        // trashed: this.filters.trashed,
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
