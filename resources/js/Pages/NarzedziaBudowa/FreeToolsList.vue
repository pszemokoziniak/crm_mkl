<template>
  <div class="max-w bg-white rounded-md shadow overflow-hidden my-5">
    <h3 class="font-medium text-xl  p-4">Dostępne narzedzia</h3>
    <form @submit.prevent="store()">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwa</th>
          <th class="pb-4 pt-6 px-6">Ilość w magazynie</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Ilość</th>
        </tr>
        <tr v-for="item in toolsFree" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td v-if="item.ilosc_magazyn >0" class="border-t">
            <input
              v-model="form.checkedValues"
              class="ml-2 mr-2"
              type="checkbox"
              :value="item.id"

            />
            {{ item.name }}
            <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </td>
          <td v-if="item.ilosc_magazyn > 0" class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/narzedzia/${item.id}/edit`" tabindex="-1">
              {{ item.ilosc_magazyn }}
            </Link>
          </td>
          <td v-if="item.ilosc_magazyn > 0" class="border-t">
            <text-input v-model="form.ilosc[item.id]" :error="form.errors.ilosc" type="number" class="px-6 py-4 lg:w-1/2" label="" />
          </td>
          <td v-if="item.ilosc_magazyn > 0" class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/narzedzia/${item.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="toolsFree.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak narzędzi</td>
        </tr>
      </table>
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj narzędzia</loading-button>
      </div>
    </form>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
import TextInput from '@/Shared/TextInput.vue'


export default {
  components: {
    TextInput,
    Icon,
    LoadingButton,
    Link,
  },
  layout: Layout,
  props: {
    toolsFree: Object,
    organization: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        checkedValues: [],
        ilosc: [],
      }),
    }
  },
  methods: {
    store() {
      this.form.post(`/budowy/${this.organization.id}/narzedzia`)
    },
  },
}
</script>
