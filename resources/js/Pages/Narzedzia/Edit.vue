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
                <select-input v-model="form.narzedzia_typ_id" :error="form.errors.narzedzia_typ_id" label="Nazwa sprzętu (typ)" class="md:col-span-2">
                  <option value="">— wybierz —</option>
                  <option v-for="t in typy" :key="t.id" :value="t.id">{{ t.name }}</option>
                  <option value="__new__">+ Nowy typ…</option>
                </select-input>
          <text-input v-if="form.narzedzia_typ_id === '__new__'" v-model="form.new_typ_name" :error="form.errors.new_typ_name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa nowego typu" />
          <!-- Lista już używanych kategorii + możliwość wpisania nowej,
               tak samo jak przy wyborze typu sprzętu. -->
          <select-input
            v-if="form.narzedzia_typ_id === '__new__'"
            v-model="form.new_typ_kategoria_wybor"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Kategoria (grupuje modele w magazynie)"
          >
            <option value="">— bez kategorii —</option>
            <option v-for="k in kategorie" :key="k" :value="k">{{ k }}</option>
            <option value="__new__">+ Nowa kategoria…</option>
          </select-input>
          <text-input
            v-if="form.narzedzia_typ_id === '__new__' && form.new_typ_kategoria_wybor === '__new__'"
            v-model="form.new_typ_kategoria_nowa"
            :error="form.errors.new_typ_kategoria"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Nazwa nowej kategorii"
            placeholder="np. Żuraw"
          />
                <text-input v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" label="Numer seryjny" />
                <date-input v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" label="Ważność badań" />
              </div>

              <div class="border-t border-gray-100 pt-8 mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-700">Gdzie jest ten sprzęt</h3>

                <div
                  class="p-4 rounded-lg border"
                  :class="narzedzia.gdzie_jest ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200'"
                >
                  <template v-if="narzedzia.gdzie_jest">
                    <div class="text-sm text-orange-700">Na budowie</div>
                    <div class="mt-1 text-xl font-bold text-orange-900">
                      <Link :href="`/budowy/${narzedzia.gdzie_jest.id}/edit`" class="hover:underline">
                        {{ narzedzia.gdzie_jest.nazwaBud }}
                      </Link>
                    </div>
                    <div class="mt-1 text-sm text-orange-800">
                      od {{ narzedzia.gdzie_jest.od || '—' }}
                      <span v-if="narzedzia.gdzie_jest.do">do {{ narzedzia.gdzie_jest.do }}</span>
                      <span v-else class="text-orange-600">— bez daty końca</span>
                    </div>
                    <Link
                      :href="`/narzedzia/przypisanie/${narzedzia.gdzie_jest.przypisanie_id}`"
                      method="delete"
                      as="button"
                      type="button"
                      class="mt-3 text-sm text-orange-800 underline hover:text-orange-900"
                    >
                      Zdejmij z budowy
                    </Link>
                  </template>
                  <template v-else>
                    <div class="text-sm text-green-700">Stan</div>
                    <div class="mt-1 text-xl font-bold text-green-900">W magazynie</div>
                    <div class="mt-1 text-sm text-green-800">Sprzęt jest wolny — można go wydać na budowę.</div>
                  </template>
                </div>

                <!-- Historia: kiedy i gdzie ten egzemplarz stał. -->
                <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-600">Historia pobytów</h4>
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500 border-b border-gray-200">
                      <th class="py-2 pr-4">Budowa</th>
                      <th class="py-2 pr-4">Od</th>
                      <th class="py-2 pr-4">Do</th>
                      <th class="py-2">Stan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="pobyt in narzedzia.pobyty" :key="pobyt.id" class="border-b border-gray-100">
                      <td class="py-2 pr-4">
                        <Link v-if="pobyt.budowa_id" :href="`/budowy/${pobyt.budowa_id}/edit`" class="text-indigo-600 hover:underline">
                          {{ pobyt.nazwaBud }}
                        </Link>
                        <span v-else class="text-gray-400">{{ pobyt.nazwaBud }}</span>
                      </td>
                      <td class="py-2 pr-4 text-gray-700">{{ pobyt.od || '—' }}</td>
                      <td class="py-2 pr-4 text-gray-700">{{ pobyt.do || 'bez daty końca' }}</td>
                      <td class="py-2">
                        <span :class="klasaStanu(pobyt.stan)">{{ opisStanu(pobyt.stan) }}</span>
                      </td>
                    </tr>
                    <tr v-if="!narzedzia.pobyty.length">
                      <td colspan="4" class="py-4 text-gray-400">Ten sprzęt nie był jeszcze wydany na żadną budowę.</td>
                    </tr>
                  </tbody>
                </table>
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
              <loading-button :loading="form.processing" class="btn-indigo ml-auto" :disabled="zaDuzePliki.length > 0" type="submit">Zapisz zmiany</loading-button>
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
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import DateInput from '@/Shared/DateInput.vue'
import SelectInput from '@/Shared/SelectInput'
import Dropzone from '@/Shared/Dropzone.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'
import Icon from '@/Shared/Icon.vue'
import axios from 'axios'

export default {
  components: {
    DateInput,
    SelectInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
    Dropzone,
    DeleteButton,
    Icon,
  },
  layout: Layout,
  props: {
    limitPlikuMb: { type: Number, default: 2 },
    kategorie: { type: Array, default: () => [] },
    narzedzia: Object,
    photos: Array,
    documents: Array,
    typy: { type: Array, default: () => [] },
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.narzedzia.id,
        numer_seryjny: this.narzedzia.numer_seryjny,
        waznosc_badan: this.narzedzia.waznosc_badan,
        name: this.narzedzia.name,
        narzedzia_typ_id: this.narzedzia.narzedzia_typ_id ?? '',
        new_typ_name: '',
        new_typ_kategoria: '',
        new_typ_kategoria_wybor: '',
        new_typ_kategoria_nowa: '',
        ilosc_all: this.narzedzia.ilosc_all,
        photos: this.photos,
        documents: this.documents,
      }),
    }
  },
  computed: {
    // Pliki większe niż limit serwera — sprawdzamy w przeglądarce, bo taki
    // formularz nie dociera nawet do walidacji i zapis kończy się ciszą.
    zaDuzePliki() {
      const limit = this.limitPlikuMb * 1024 * 1024
      const wszystkie = [...(this.form.photos || []), ...(this.form.documents || [])]

      return wszystkie
        .filter((p) => p && !p.deleted && p.size > limit)
        .map((p) => `${p.name} (${(p.size / 1024 / 1024).toFixed(1)} MB)`)
    },
  },
  watch: {
    'form.new_typ_kategoria_wybor': function (wybor) {
      this.form.new_typ_kategoria = wybor === '__new__' ? this.form.new_typ_kategoria_nowa : wybor
    },
    'form.new_typ_kategoria_nowa': function (nazwa) {
      if (this.form.new_typ_kategoria_wybor === '__new__') {
        this.form.new_typ_kategoria = nazwa
      }
    },
  },
  methods: {
    opisStanu(stan) {
      if (stan === 'trwa') return 'trwa'
      if (stan === 'zaplanowany') return 'zaplanowany'
      return 'zakończony'
    },
    klasaStanu(stan) {
      if (stan === 'trwa') return 'text-orange-700 font-semibold'
      if (stan === 'zaplanowany') return 'text-indigo-600'
      return 'text-gray-400'
    },
    update() {
      this.form
        .transform((data) => ({
          ...data,
          narzedzia_typ_id: data.narzedzia_typ_id === '__new__' ? null : data.narzedzia_typ_id,
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
