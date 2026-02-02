<template>
  <div>
    <Head title="Budowa" />
    <h1 class="mb-8 text-3xl font-bold">
      Budowy <span class="text-indigo-400 font-medium">{{ titleSuffix }}</span>
    </h1>

    <div class="flex flex-col gap-4 items-start justify-between mb-6 sm:flex-row sm:items-center">
      <div class="flex items-center w-full sm:w-auto">
        <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
          <label class="block text-gray-700">Filtruj:</label>
          <select v-model="form.trashed" class="form-select mt-1 w-full">
            <option :value="null">Budowy aktywne</option>
            <option value="only">Budowy zakończone</option>
          </select>
        </search-filter>
      </div>

      <Link class="btn-indigo w-full text-center sm:w-auto" href="/budowy/create">
        <span>Utwórz</span>
        <span class="hidden md:inline">&nbsp;Budowę</span>
      </Link>
    </div>

    <div class="bg-white rounded-md shadow overflow-hidden">
      <OrganizationsMobileList :organizations="organizations.data" />
      <OrganizationsDesktopTable :organizations="organizations.data" :sort="form.sort" :direction="form.direction" @sort="onSort" />
    </div>

    <pagination class="mt-6" :links="organizations.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import Layout from '@/Shared/Layout'
import Pagination from '@/Shared/Pagination'
import SearchFilter from '@/Shared/SearchFilter'

import OrganizationsMobileList from './Partials/OrganizationsMobileList'
import OrganizationsDesktopTable from './Partials/OrganizationsDesktopTable'

export default {
  components: {
    Head,
    Link,
    Pagination,
    SearchFilter,
    OrganizationsMobileList,
    OrganizationsDesktopTable,
  },
  layout: Layout,
  props: {
    filters: Object,
    organizations: Object,
  },
  data() {
    return {
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
        sort: this.filters.sort ?? 'nazwaBud',
        direction: this.filters.direction ?? 'asc',
      },
    }
  },
  computed: {
    titleSuffix() {
      if (this.form.trashed === 'with') return '/ Wszystkie'
      if (this.form.trashed === 'only') return '/ Zakończone'
      return '/ Aktywne'
    },
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
    onSort(column) {
      if (this.form.sort === column) {
        this.form.direction = this.form.direction === 'asc' ? 'desc' : 'asc'
      } else {
        this.form.sort = column
        this.form.direction = 'asc'
      }
    },
  },
}
</script>
