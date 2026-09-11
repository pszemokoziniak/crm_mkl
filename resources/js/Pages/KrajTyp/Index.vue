<template>
  <div>
    <Head title="Kraje" />
    <h1 class="mb-8 text-3xl font-bold">Kraje</h1>
    <div class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" href="/krajTyp/create">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="naglowek-tabeli">
          <th>Nazwa</th>
          <th>A1</th>
        </tr>
        <tr v-for="item in krajTypes" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/krajTyp/${item.id}/edit`">
              {{ item.name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 text-sm" :href="`/krajTyp/${item.id}/edit`" tabindex="-1">
              <span v-if="item.wymaga_a1" class="text-gray-700">wymagane</span>
              <span v-else class="text-gray-400">niewymagane</span>
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/krajTyp/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
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
    krajTypes: Object,
  },
  data() {
    return {
      form: {},
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/krajTyp', pickBy(this.form), { preserveState: true })
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
