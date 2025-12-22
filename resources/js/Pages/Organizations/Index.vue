<template>
  <div>
    <Head title="Budowa" />
    <h1 class="mb-8 text-3xl font-bold">Budowy</h1>

    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null">Budowy aktywne</option>
          <option value="only">Zakończona</option>
        </select>
      </search-filter>

      <Link class="btn-indigo" href="/budowy/create">
        <span>Utwórz</span>
        <span class="hidden md:inline">&nbsp;Budowę</span>
      </Link>
    </div>

    <div class="bg-white rounded-md shadow">
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
