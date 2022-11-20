<template>
  <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
    <form @submit.prevent="store()">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Pozycja</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
        </tr>
        <tr v-for="free in contactsFree.data" :key="free.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <input
              class="ml-2 mr-2"
              type="checkbox"
              :value="free.id"
              v-model="form.checkedValues"
            />
            {{ free.first_name }} {{ free.last_name }}
            <icon v-if="free.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${data.org.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.funkcja.name }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${data.org.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.phone }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/pracownicy/${data.org.id}/destroy/${free.id}`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
          </td>
        </tr>
        <tr v-if="contactsFree.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
        </tr>
      </table>
      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj pracowników</loading-button>
      </div>
    </form>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import LoadingButton from '@/Shared/LoadingButton'
// import mapValues from 'lodash/mapValues'

export default {
  components: {
    Icon,
    LoadingButton,
    Link,
  },
  layout: Layout,
  props: {
    data: Object,
    contacts: Object,
    contactsFree: Object,
    dates: Object,
  },
  remember: 'form',
  data() {
    return {
      start: '',
      end: '',
      form: this.$inertia.form({
        checkedValues: [],
      }),
    }
  },
  methods: {
    store() {
      this.form.post(`/pracownicy/${this.data.org.id}/?start=${this.dates.start}&end=${this.dates.end}`)
    },
  },
  mounted: function() {
    // console.log(this.start)
  },
}
</script>
