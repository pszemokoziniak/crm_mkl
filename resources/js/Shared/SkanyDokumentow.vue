<template>
  <div>
    <h2 class="mt-10 mb-4 text-xl font-bold">{{ tytul }}</h2>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa</th>
            <th class="pb-4 pt-6 px-6">Plik</th>
            <th class="pb-4 pt-6 px-6" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="dokument in documents.data"
            :key="dokument.id"
            class="hover:bg-gray-100 focus-within:bg-gray-100"
            :class="dokument.deleted_at ? 'text-gray-400' : ''"
          >
            <td class="border-t px-6 py-4">
              <a
                v-if="!dokument.deleted_at"
                target="_blank"
                :href="adresPliku(dokument)"
                class="hover:text-indigo-600 focus:text-indigo-500"
              >{{ dokument.name }}</a>
              <span v-else>{{ dokument.name }}</span>
              <span v-if="dokument.deleted_at" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-600">w koszu</span>
            </td>
            <td class="border-t px-6 py-4 text-gray-500">{{ dokument.filename }}</td>
            <td class="border-t px-6 py-4">
              <div class="flex items-center justify-end gap-3">
                <a
                  v-if="!dokument.deleted_at"
                  target="_blank"
                  :href="adresPliku(dokument)"
                  class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800"
                >
                  <DocumentDownloadIcon class="h-4 w-4" />
                  <span>Pobierz</span>
                </a>
                <button
                  v-if="!kierownik && dokument.deleted_at"
                  type="button"
                  class="text-sm text-indigo-600 hover:text-indigo-800"
                  @click="przywroc(dokument)"
                >Przywróć</button>
                <button
                  v-else-if="!kierownik"
                  type="button"
                  class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-800"
                  @click="usun(dokument)"
                >
                  <TrashIcon class="h-4 w-4" />
                  <span>Usuń</span>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="documents.data.length === 0">
            <td class="px-6 py-6 border-t text-gray-400" colspan="3">
              Brak skanów. Wgrywa się je w zakładce
              <Link class="text-indigo-600 hover:underline" :href="`/contacts/${contactId}/documents`">Dokumenty</Link>.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import { DocumentDownloadIcon, TrashIcon } from '@heroicons/vue/solid'

// Ta tabela była przepisana osobno na pięciu ekranach pracownika (Badania,
// A1, BHP, Uprawnienia, PBIOZ) i zdążyła się już rozjechać. Jedno miejsce
// zamiast pięciu — poprawka trafia od razu wszędzie.
export default {
  components: { DocumentDownloadIcon, Link, TrashIcon },
  props: {
    contactId: { type: [Number, String], required: true },
    documents: { type: Object, required: true },
    kierownik: { type: Boolean, default: false },
    tytul: { type: String, default: 'Skany' },
    // Każdy ekran kasuje swoim wariantem trasy, żeby wrócić na siebie.
    trasaUsuwania: { type: String, required: true },
  },
  methods: {
    adresPliku(dokument) {
      return `/contacts/${this.contactId}/documents/${dokument.id}`
    },
    usun(dokument) {
      if (confirm('Przenieść ten skan do kosza?')) {
        this.$inertia.delete(`${this.adresPliku(dokument)}/${this.trasaUsuwania}`, { preserveScroll: true })
      }
    },
    przywroc(dokument) {
      this.$inertia.put(`${this.adresPliku(dokument)}/restore`, {}, { preserveScroll: true })
    },
  },
}
</script>
