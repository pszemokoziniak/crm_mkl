<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />
    <div>
      <WorkerMenu :contactId="contactId" />
    </div>
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownik</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ contact.first_name }} {{ contact.last_name }}
    </h1>
    <h1 class="mb-8 text-2xl font-bold">Nieobecność</h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <select-input v-model="form.shift_status_id" :error="form.errors.shift_status_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Powód">
            <option :value="null">— wybierz —</option>
            <option v-for="powod in powody" :key="powod.id" :value="powod.id">{{ powod.title }} ({{ powod.code }})</option>
          </select-input>
          <div class="pb-8 pr-6 w-full lg:w-1/2" />
          <text-input v-model="form.start" :error="form.errors.start" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Od" />
          <text-input v-model="form.end" :error="form.errors.end" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Do" />

          <div v-if="kolidujacyPobyt" class="pb-8 pr-6 w-full">
            <p class="p-3 text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 rounded">
              W tym terminie pracownik jest przypisany do budowy
              <span class="font-semibold">{{ kolidujacyPobyt.nazwaBud || 'bez nazwy' }}</span>
              ({{ kolidujacyPobyt.start }} → {{ kolidujacyPobyt.end || 'bez końca' }}).
              Zapis jest możliwy — przypisanie do budowy zostaje bez zmian.
            </p>
          </div>
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!holiday.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    WorkerMenu,
  },
  layout: Layout,
  props: {
    contact: Object,
    holiday: Object,
    powody: { type: Array, default: () => [] },
    pobyty: { type: Array, default: () => [] },
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      form: this.$inertia.form({
        id: this.holiday.id,
        start: this.holiday.start,
        end: this.holiday.end,
        shift_status_id: this.holiday.shift_status_id,
      }),
    }
  },
  computed: {
    /** Pobyt na budowie zachodzący na wpisywany termin — podstawa ostrzeżenia. */
    kolidujacyPobyt() {
      if (!this.form.start || !this.form.end) {
        return null
      }

      return this.pobyty.find((p) => p.start <= this.form.end && (!p.end || p.end >= this.form.start)) || null
    },
  },
  methods: {
    update() {
      this.form.put(`/contacts/${this.contact.id}/holiday/${this.form.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/holiday/${this.form.id}`)
      }
    },
  },
}
</script>
