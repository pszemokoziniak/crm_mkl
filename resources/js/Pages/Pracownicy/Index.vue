<template>
  <div>
    <Head title="Budowa" />
    <BudMenu :budId="organization_id" />
    <h1 class="mb-2 text-3xl font-bold">Budowa Pracownicy</h1>
    <p class="mb-6 text-sm text-gray-500">
      Na budowie obecnie: <span class="font-bold text-gray-700">{{ naBudowie }}</span>
      z {{ contactworkdates.data.length }} wpisów na tej stronie.
      <span v-if="zakonczone > 0">Pozostałe {{ zakonczone }} to zakończone pobyty — zostają jako historia.</span>
    </p>
    <div class="flex items-center justify-between mb-6">
      <search-filter-no-filtr v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Wybierz:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option value="with">Wszystkie</option>
          <option value="only">Usunięte</option>
        </select>
      </search-filter-no-filtr>
      <Link v-if="user_owner !== 3" class="btn-indigo" :href="`/pracownicy/${organization_id}/create`">
        <span>Dodaj Pracownika</span>
      </Link>
    </div>
    <!-- Zbiorcze skrócenie pobytu — przy przenoszeniu ekipy na inną budowę. -->
    <div v-if="mozeEdytowac && zaznaczone.length" class="flex flex-wrap items-end gap-4 mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-md">
      <div>
        <div class="text-sm font-semibold text-gray-800">Zaznaczono: {{ zaznaczone.length }}</div>
        <div class="text-xs text-gray-500">Ustaw wszystkim ten sam koniec pobytu na tej budowie.</div>
      </div>
      <div>
        <label class="form-label">Koniec pobytu:</label>
        <input v-model="nowaDataKonca" type="date" class="form-input mt-1" />
      </div>
      <button type="button" class="btn-indigo" :disabled="!nowaDataKonca" @click="ustawDateKonca">
        Ustaw datę końca
      </button>
      <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="zaznaczone = []">
        Wyczyść zaznaczenie
      </button>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <tr class="text-left font-bold">
          <th v-if="mozeEdytowac" class="pb-4 pt-6 px-4 w-px">
            <input type="checkbox" class="form-checkbox" :checked="wszystkieZaznaczone" @change="przelaczWszystkie" />
          </th>
          <th class="pb-4 pt-6 px-6">Nazwisko Imię</th>
          <th class="pb-4 pt-6 px-6">Czas Pracy</th>
          <th class="pb-4 pt-6 px-6">Stanowisko</th>
          <th class="pb-4 pt-6 px-6">Na budowie</th>
          <th class="pb-4 pt-6 px-6">Zatrudnienie</th>
        </tr>
        <tr v-for="item in contactworkdates.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td v-if="mozeEdytowac" class="border-t px-4">
            <input v-model="zaznaczone" type="checkbox" class="form-checkbox" :value="item.id" />
          </td>
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              {{ item.contact.last_name }} {{ item.contact.first_name }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              od: {{ item.start }}  do: {{ item.end }}
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              <div v-if="item.contact.funkcja">
                {{ item.contact.funkcja.name }}
              </div>
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <!-- Pobyt na tej budowie — to co wcześniej trzeba było wyczytać z dat -->
          <td v-if="item.contact" class="border-t">
            <div class="px-6 py-4">
              <!-- Nieobecność ma pierwszeństwo: pracownik nadal należy do budowy,
                   ale dziś go na niej nie ma. -->
              <span v-if="item.on_site && item.nieobecnosc" class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-full">
                {{ item.nieobecnosc }}
              </span>
              <span v-else-if="item.on_site" class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-green-800 bg-green-100 border border-green-200 rounded-full">
                Pracuje
              </span>
              <span v-else class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-gray-600 bg-gray-100 border border-gray-200 rounded-full">
                Zakończony {{ item.end }}
              </span>
            </div>
          </td>
          <!-- Status zatrudnienia w firmie — nie mówi nic o tej budowie -->
          <td v-if="item.contact" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/contacts/${item.contact.id}/edit`">
              <div v-if="item.contact.status_zatrudnienia" class="text-gray-500">
                {{ item.contact.status_zatrudnienia }}
              </div>
              <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
          </td>
          <td v-if="item.contact" class="w-px border-t p-2">
            <Link v-if="user_owner !== 3" class="flex items-center px-4 mb-4 underline text-indigo-600" tabindex="-1" :href="`/pracownicy/${organization_id}/edit/${item.id}`">
              Popraw daty
            </Link>
            <Link v-if="user_owner !== 3" class="flex items-center px-4 underline text-indigo-600" tabindex="-1" @click="destroy(item.id)">
              Usuń
            </Link>
          </td>
        </tr>
        <tr v-if="contactworkdates === null">
          <td class="px-6 py-4 border-t" colspan="7">Nie znaleziono pracownika</td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu.vue'
import throttle from 'lodash/throttle'
import pickBy from 'lodash/pickBy'
import mapValues from 'lodash/mapValues'
import SearchFilterNoFiltr from '@/Shared/SearchFilterNoFiltr.vue'


export default {
  components: {
    SearchFilterNoFiltr,
    Head,
    Icon,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    contactworkdates: Object,
    organization_id: Number,
    filters: Object,
    user_owner: Number,
    // contact_work_dates: Object,
  },
  data() {
    return {
      zaznaczone: [],
      nowaDataKonca: '',
      form: {
        search: this.filters.search,
        trashed: this.filters.trashed,
      },
    }
  },
  computed: {
    mozeEdytowac() {
      return this.user_owner !== 3
    },
    wszystkieZaznaczone() {
      return this.contactworkdates.data.length > 0
        && this.zaznaczone.length === this.contactworkdates.data.length
    },
    naBudowie() {
      return this.contactworkdates.data.filter((item) => item.on_site).length
    },
    zakonczone() {
      return this.contactworkdates.data.length - this.naBudowie
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/pracownicy/'+ this.organization_id, pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    przelaczWszystkie(event) {
      this.zaznaczone = event.target.checked
        ? this.contactworkdates.data.map((item) => item.id)
        : []
    },
    ustawDateKonca() {
      const ile = this.zaznaczone.length

      if (!confirm(`Ustawić koniec pobytu na ${this.nowaDataKonca} dla ${ile} ${ile === 1 ? 'osoby' : 'osób'}?`)) {
        return
      }

      this.$inertia.put(`/pracownicy/${this.organization_id}/data-konca`, {
        ids: this.zaznaczone,
        end: this.nowaDataKonca,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          this.zaznaczone = []
          this.nowaDataKonca = ''
        },
      })
    },
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    destroy(worker) {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/pracownicy/${worker}`)
      }
    },
  },
}
</script>
