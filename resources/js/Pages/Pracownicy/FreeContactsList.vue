<template>
  <div class="max-w my-5 bg-white rounded-md shadow overflow-hidden">
    <h3 class="p-4 text-xl font-medium">Przypisz kierownika i inżyniera</h3>

    <div class="grid gap-4 grid-cols-1 p-4 md:grid-cols-2">
      <div>
        <label class="block mb-1 text-sm font-medium">Kierownik</label>
        <select v-model="form.manager_id" class="form-select w-full">
          <option :value="null">— wybierz —</option>
          <option v-for="p in managers" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }} ({{ p.fn_name }})</option>
        </select>
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium">Inżynier</label>
        <select v-model="form.engineer_id" class="form-select w-full">
          <option :value="null">— wybierz —</option>
          <option v-for="p in engineers" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }} ({{ p.fn_name }})</option>
        </select>
      </div>
    </div>
    <h3 class="p-4 text-xl font-medium">Dostępni pracownicy</h3>
    <form @submit.prevent="store()">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Pozycja</th>
          <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
        </tr>
        <tr v-for="free in contactsFree" :key="free.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <input class="ml-2 mr-2" type="checkbox" :value="free.id" v-model="form.checkedValues" />
            {{ free.last_name }} {{ free.first_name }}
            <icon v-if="free.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.fn_name }}
            </Link>
          </td>
          <td class="border-t">
            <Link class="flex items-center px-6 py-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
              {{ free.phone }}
            </Link>
          </td>
          <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="`/pracownicy/${organization.id}/destroy/${free.id}`" tabindex="-1">
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
    contacts: Object,
    contactsFree: Object,
    specialists: Object,
    organization: Object,
    start: String,
    end: String,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        manager_id: null,
        engineer_id: null,
        checkedValues: [],
        start: this.start,
        end: this.end,
      }),
    }
  },
  computed: {
    managers() {
      return (this.specialists || []).filter((x) => x.funkcja_id === 1)
    },
    engineers() {
      return (this.specialists || []).filter((x) => x.funkcja_id === 6)
    },
  },
  methods: {
    store() {
      this.form.post(`/pracownicy/${this.organization.id}/`)
    },
  },
}
</script>
