<template>
  <div>
    <Head :title="`${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedzia">Magazyn Sprzętu</Link>
      <span class="text-indigo-400 font-medium"> / </span>
      {{ form.name }}
    </h1>
    <trashed-message v-if="narzedzia.deleted_at" class="mb-6" @restore="restore">Ten sprzęt został usunięty.</trashed-message>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Główny formularz -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-md shadow overflow-hidden">
          <form @submit.prevent="update">
            <div class="p-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <text-input v-model="form.name" :error="form.errors.name" label="Nazwa sprzętu" class="md:col-span-2" />
                <text-input v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" label="Numer seryjny" />
                <date-input v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" label="Ważność badań" />
              </div>

              <div class="border-t border-gray-100 pt-8 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">Zarządzanie ilością</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <label class="block text-sm font-medium text-gray-500 mb-1">Łącznie sztuk</label>
                    <number-input v-model="form.ilosc_all" :error="form.errors.ilosc_all" class="w-full" />
                    <p class="mt-1 text-xs text-gray-400">Całkowita ilość w firmie</p>
                  </div>

                  <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <label class="block text-sm font-medium text-blue-600 mb-1">Na budowach</label>
                    <div class="text-2xl font-bold text-blue-800">{{ narzedzia.ilosc_budowa }}</div>
                    <p class="mt-1 text-xs text-blue-400">Aktualnie przypisane</p>
                  </div>

                  <div :class="[
                    'p-4 rounded-lg border',
                    iloscMagazyn > 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'
                  ]">
                    <label :class="['block text-sm font-medium mb-1', iloscMagazyn > 0 ? 'text-green-600' : 'text-red-600']">
                      W magazynie
                    </label>
                    <div :class="['text-2xl font-bold', iloscMagazyn > 0 ? 'text-green-800' : 'text-red-800']">
                      {{ iloscMagazyn }}
                    </div>
                    <p :class="['mt-1 text-xs', iloscMagazyn > 0 ? 'text-green-400' : 'text-red-400']">
                      Dostępne do wydania
                    </p>
                  </div>
                </div>
                <div v-if="iloscMagazyn < 0" class="mt-4 p-3 bg-red-100 text-red-700 text-sm rounded border border-red-200 flex items-center">
                  <icon name="trash" class="w-4 h-4 mr-2 fill-current" />
                  Uwaga: Ilość na budowach przekracza ilość całkowitą! Sprawdź stany.
                </div>
              </div>

              <div class="border-t border-gray-100 pt-8">
                <div class="mb-8">
                  <div class="form-label mb-2">Zdjęcia sprzętu</div>
                  <dropzone v-model="form.photos" :extensions="['jpg', 'jpeg', 'png', 'tiff']" />
                </div>
                <div>
                  <div class="form-label mb-2">Dokumentacja (PDF, Instrukcje, Certyfikaty)</div>
                  <dropzone v-model="form.documents" :extensions="['pdf', 'xls', 'xlsx', 'doc', 'docx']" />
                </div>
              </div>
            </div>
            <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
              <delete-button
                v-if="!narzedzia.deleted_at"
                :href="`/narzedzia/${narzedzia.id}`"
                confirm="Czy na pewno chcesz usunąć ten sprzęt z bazy?"
              >
                Usuń sprzęt
              </delete-button>
              <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz zmiany</loading-button>
            </div>
          </form>
        </div>
      </div>

      <!-- Prawy panel informacyjny (opcjonalnie) -->
      <div class="space-y-6">
        <div class="bg-white p-6 rounded-md shadow">
          <h3 class="font-bold text-gray-700 mb-4 flex items-center">
            <icon name="office" class="w-5 h-5 mr-2 fill-gray-400" />
            Status urządzenia
          </h3>
          <ul class="space-y-4 text-sm">
            <li class="flex justify-between border-b border-gray-50 pb-2">
              <span class="text-gray-500">Ostatnia aktualizacja:</span>
              <span class="font-medium text-gray-700">{{ new Date().toLocaleDateString() }}</span>
            </li>
            <li class="flex justify-between border-b border-gray-50 pb-2">
              <span class="text-gray-500">Badania techniczne:</span>
              <span :class="['font-medium', isExpired(narzedzia.waznosc_badan) ? 'text-red-600' : 'text-green-600']">
                {{ narzedzia.waznosc_badan || 'Brak danych' }}
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {Head, Link} from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import NumberInput from '@/Shared/NumberInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import DateInput from '@/Shared/DateInput.vue'
import Dropzone from '@/Shared/Dropzone.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'
import Icon from '@/Shared/Icon.vue'
import axios from 'axios'

export default {
  components: {
    DateInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    NumberInput,
    TrashedMessage,
    Dropzone,
    DeleteButton,
    Icon,
  },
  layout: Layout,
  props: {
    narzedzia: Object,
    photos: Array,
    documents: Array,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.narzedzia.id,
        numer_seryjny: this.narzedzia.numer_seryjny,
        waznosc_badan: this.narzedzia.waznosc_badan,
        name: this.narzedzia.name,
        ilosc_all: this.narzedzia.ilosc_all,
        photos: this.photos,
        documents: this.documents,
      }),
    }
  },
  computed: {
    iloscMagazyn() {
      return (parseInt(this.form.ilosc_all) || 0) - (parseInt(this.narzedzia.ilosc_budowa) || 0)
    },
  },
  methods: {
    update() {
      this.form
        .transform((data) => ({
          ...data,
          photos: data.photos.filter(file => file.deleted !== true),
          documents: data.documents.filter(file => file.deleted !== true),
        }))
        .post(`/narzedzia/${this.narzedzia.id}`, {
          onBefore: () => {
            const photosToDelete = this
              .form
              .photos
              .filter(file => file.deleted === true)
              .map(file => file.name)

            const documentsToDelete = this
              .form
              .documents
              .filter(file => file.deleted === true)
              .map(file => file.name)

            const filesToDelete = [...photosToDelete, ...documentsToDelete]

            if (filesToDelete.length > 0) {
              axios.delete(`/narzedzia/${this.narzedzia.id}/file`, {
                data: {
                  files: filesToDelete,
                },
              })
            }
          },
        })
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/narzedzia/${this.narzedzia.id}/restore`)
      }
    },
    isExpired(date) {
      if (!date) return false
      return new Date(date) < new Date()
    },
  },
}
</script>
