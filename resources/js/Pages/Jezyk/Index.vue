<template>
  <div>
    <Head title="Język" />
    <div>
      <WorkerMenu :contactId="contactId" :userOwner="userOwner"/>
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
    </h1>
    <h1 class="mb-8 text-3xl font-bold">Języki</h1>
    <div class="flex items-center justify-between mb-6">
      <Link v-if="userOwner !== 3" class="btn-indigo" :href="`/contacts/${contact.id}/jezyk/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Język</th>
          <th class="pb-4 pt-6 px-6">Poziom</th>
        </tr>
        <tr v-for="item in jezyks.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/jezyk/${item.id}/edit`">
              <div v-if="item.jezyk">
                {{ item.jezyk.name }}
              </div>
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/jezyk/${item.id}/edit`">
              {{ item.poziom }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="userOwner === 3 ? '' : `/contacts/${contact.id}/jezyk/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <!-- <tr v-if="accounts.data.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pozycji</td>
        </tr> -->
      </table>
    </div>
    <!-- <pagination class="mt-6" :links="accounts.links" /> -->
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import WorkerMenu from '@/Shared/WorkerMenu'


export default {
  components: {
    Head,
    Icon,
    Link,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    jezyks: Object,
    contact: Object,
    userOwner: Number,
    // badanias: Object,
    // badaniaTyp: Object,
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
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/jezyk', pickBy(this.form), { preserveState: true })
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
