<template>
  <div>
    <Head title="Kierownictwo budowy" />
    <BudMenu :budId="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">Kierownictwo budowy</h1>

    <div class="max-w bg-white rounded-md shadow overflow-hidden mb-8">
      <h3 class="p-4 text-xl font-medium">Dodaj Kierownika / Inżyniera</h3>
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-3 -mr-6 p-8">
          <date-input v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek pracy" />
          <date-input v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec pracy" />

          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <label class="form-label">Pracownik (Kierownik/Inżynier):</label>
            <select v-model="form.contact_id" class="form-select w-full" :class="{ error: form.errors.contact_id }">
              <option :value="null" />
              <option v-for="p in specialists" :key="p.id" :value="p.id">{{ p.last_name }} {{ p.first_name }} ({{ p.fn_name }})</option>
            </select>
            <div v-if="form.errors.contact_id" class="form-error">{{ form.errors.contact_id }}</div>
          </div>
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj do kierownictwa</loading-button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <h3 class="p-4 text-xl font-medium">Aktualne kierownictwo</h3>
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Czas Pracy</th>
          <th class="pb-4 pt-6 px-6">Stanowisko</th>
          <th class="pb-4 pt-6 px-6">Akcje</th>
        </tr>
        <tr v-for="item in management" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t px-6 py-4">
            {{ item.last_name }} {{ item.first_name }}
          </td>
          <td class="border-t px-6 py-4">
            od: {{ item.start }} do: {{ item.end }}
          </td>
          <td class="border-t px-6 py-4">
            {{ item.name }}
          </td>
          <td class="border-t px-6 py-4">
            <Link class="text-indigo-600 hover:underline mr-4" :href="`/pracownicy/${organization.id}/edit/${item.id}`">Popraw</Link>
            <button class="text-red-600 hover:underline" @click="destroy(item.id)">Usuń</button>
          </td>
        </tr>
        <tr v-if="management.length === 0">
          <td class="px-6 py-4 border-t" colspan="4">Brak przypisanego kierownictwa.</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu'
import DateInput from '@/Shared/DateInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    Link,
    BudMenu,
    DateInput,
    LoadingButton,
  },
  layout: Layout,
  props: {
    organization: Object,
    specialists: Array,
    management: Array,
  },
  data() {
    return {
      form: this.$inertia.form({
        contact_id: null,
        start: '',
        end: '',
      }),
    }
  },
  methods: {
    store() {
      this.form.post(`/budowy/${this.organization.id}/kierownictwo`, {
        onSuccess: () => {
          this.form.reset('contact_id')
        },
      })
    },
    destroy(id) {
      if (confirm('Czy na pewno chcesz usunąć tę osobę z kierownictwa?')) {
        this.$inertia.delete(`/pracownicy/${id}`)
      }
    },
  },
}
</script>
