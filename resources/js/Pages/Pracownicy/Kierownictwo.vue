<template>
  <div>
    <Head title="Kierownictwo budowy" />
    <BudMenu :budId="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">Kierownictwo budowy</h1>

    <div v-if="!$page.props.permissions.kierownik" class="max-w bg-white rounded-md shadow overflow-hidden mb-8">
      <h3 class="p-4 text-xl font-medium">Dodaj Kierownika / Inżyniera</h3>
      <form @submit.prevent="openConfirm">
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

    <teleport to="body">
      <div v-if="showConfirm" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showConfirm = false" />
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Potwierdź dodanie do kierownictwa</h3>
          </div>
          <div class="px-6 py-4 text-sm text-gray-700 space-y-1">
            <p><span class="text-gray-500">Pracownik:</span> {{ wybranyPracownik }}</p>
            <p><span class="text-gray-500">Budowa:</span> <span class="font-medium">{{ organization.nazwaBud }}</span></p>
            <p><span class="text-gray-500">Termin:</span> {{ form.start }} → {{ form.end }}</p>

            <!-- Ten sam termin na innej budowie. -->
            <div v-if="kolidujacePobyty.length" class="mt-3 p-3 text-red-700 bg-red-50 border border-red-200 rounded space-y-1">
              <p class="font-semibold">Uwaga — w tym samym czasie pracownik jest już na budowie:</p>
              <p v-for="(pobyt, i) in kolidujacePobyty" :key="i">
                <span class="font-semibold">{{ pobyt.nazwaBud || 'budowa usunięta' }}</span>
                — od {{ pobyt.start }} do {{ pobyt.end || 'bez końca' }}
              </p>
              <p class="text-xs">
                Stanowisko kierownicze może obsługiwać kilka budów naraz — jeśli to celowe, potwierdź.
              </p>
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50" @click="showConfirm = false">Anuluj</button>
            <button type="button" class="btn-indigo" :disabled="form.processing" @click="store">Potwierdź</button>
          </div>
        </div>
      </div>
    </teleport>

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
            <template v-if="!$page.props.permissions.kierownik">
              <Link class="text-indigo-600 hover:underline mr-4" :href="`/pracownicy/${organization.id}/edit/${item.id}`">Popraw</Link>
              <button class="text-red-600 hover:underline" @click="destroy(item.id)">Usuń</button>
            </template>
            <span v-else class="text-gray-400">—</span>
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
    pobyty: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      showConfirm: false,
      form: this.$inertia.form({
        contact_id: null,
        start: '',
        end: '',
      }),
    }
  },
  computed: {
    wybranyPracownik() {
      const osoba = this.specialists.find((p) => p.id === this.form.contact_id)

      return osoba ? `${osoba.last_name} ${osoba.first_name} (${osoba.fn_name})` : '—'
    },
    /** Pobyty wybranej osoby zachodzące na wpisany termin. */
    kolidujacePobyty() {
      if (!this.form.contact_id || !this.form.start || !this.form.end) {
        return []
      }

      const pobyty = this.pobyty[this.form.contact_id] || []

      return pobyty.filter((p) => p.start <= this.form.end && (!p.end || p.end >= this.form.start))
    },
  },
  methods: {
    openConfirm() {
      if (!this.form.contact_id || !this.form.start || !this.form.end) {
        // Braki w formularzu pokaże zwykła walidacja po stronie serwera.
        this.store()
        return
      }

      this.showConfirm = true
    },
    store() {
      this.form.post(`/budowy/${this.organization.id}/kierownictwo`, {
        onSuccess: () => {
          this.form.reset('contact_id')
        },
        onFinish: () => {
          this.showConfirm = false
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
