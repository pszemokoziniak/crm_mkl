<template>
  <div>
    <BudMenu :budId="budId" />
    <Head title="Klient" />
    <budowa-naglowek :bud-id="organization.id" :nazwa="organization.nazwaBud" tytul="Dane klienta" />
    <!-- Klient budowy jest wpisany w jej karcie; tabela niżej to osoby
         kontaktowe po jego stronie, wypełniane osobno. -->
    <div class="mb-6 p-4 bg-white rounded-md shadow">
      <div class="text-xs uppercase tracking-wider text-gray-500">Klient tej budowy</div>
      <div class="mt-1 text-xl font-semibold text-gray-900">{{ organization.klient || 'nie wpisano' }}</div>
      <Link :href="`/budowy/${organization.id}/edit`" class="mt-1 inline-block text-sm text-indigo-600 hover:underline">
        Zmień w karcie budowy
      </Link>
    </div>

    <h2 class="mb-2 font-semibold text-gray-700">Osoby kontaktowe</h2>

    <div v-if="!$page.props.permissions.kierownik" class="flex items-center justify-between mb-6">
      <Link class="btn-indigo" :href="`/budowy/${budId}/klient/create`">
        <span>Dodaj</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-nowrap">
        <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Nazwa Firmy</th>
            <th class="pb-4 pt-6 px-6">Osoba</th>
            <th class="pb-4 pt-6 px-6" colspan="2">Telefon</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in klients" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <td class="border-t">
              <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="$page.props.permissions.kierownik ? '' : `/budowy/${budId}/klient/${item.id}/edit`">
                {{ item.nameFirma }}
                <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="$page.props.permissions.kierownik ? '' : `/budowy/${budId}/klient/${item.id}/edit`" tabindex="-1">
                {{ item.nameKontakt }}
              </Link>
            </td>
            <td class="border-t">
              <Link class="flex items-center px-6 py-4" :href="$page.props.permissions.kierownik ? '' : `/budowy/${budId}/klient/${item.id}/edit`" tabindex="-1">
                {{ item.phone }}
              </Link>
            </td>
            <td class="w-px border-t">
              <Link class="flex items-center px-4" :href="$page.props.permissions.kierownik ? '' : `/budowy/${budId}/klient/${item.id}/edit`" tabindex="-1">
                <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
              </Link>
            </td>
          </tr>
<!--          <tr v-if="klients.data.length === 0">-->
<!--            <td class="px-6 py-4 border-t" colspan="4">Brak danych.</td>-->
<!--          </tr>-->
        </tbody>
      </table>
    </div>
<!--    <pagination class="mt-6" :links="organizations.links" />-->
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import BudowaNaglowek from '@/Shared/BudowaNaglowek'
import Icon from '@/Shared/Icon'
import BudMenu from '@/Shared/BudMenu'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
// import Pagination from '@/Shared/Pagination'

export default {
  components: {
    BudowaNaglowek,
    Head,
    Icon,
    Link,
    BudMenu,
    // Pagination,
  },
  layout: Layout,
  props: {
    filters: Object,
    organizations: Object,
    klients: Object,
    budId: Number,
    organization: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      form: {
        // search: this.filters.search,
        // trashed: this.filters.trashed,
      },
    }
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/budowy', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
  },
}
</script>
