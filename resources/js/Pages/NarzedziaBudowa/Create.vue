<template>
<!--  <Head :title="organization.name" />-->
  <BudMenu :budId="budId" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy"> {{organization.name }}</Link>
    <span class="text-indigo-400 font-medium">/</span>
    <p class="text-base">Dodaj sprzęt do budowy</p>
  </h1>
  <div v-if="toolsFree" class="max-w">
    <FreeToolsList :toolsFree="toolsFree" :organization="organization" />
  </div>

  <div class="max-w bg-white rounded-md shadow overflow-hidden my-5">
    <h3 class="font-medium text-xl p-4">Sprzęt na budowie</h3>
    <table class="w-full whitespace-nowrap">
      <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Nazwa</th>
        <th class="pb-4 pt-6 px-6">Numer seryjny</th>
        <th class="pb-4 pt-6 px-6">Ważność badań</th>
        <th class="pb-4 pt-6 px-6 text-center">Ilość</th>
        <th class="pb-4 pt-6 px-6 text-right">Akcje</th>
      </tr>
      <tr v-for="item in toolsOnBuild.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.narzedzia.id}/edit`">
            {{ item.narzedzia.name }}
            <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.narzedzia.id}/edit`">
            {{ item.narzedzia.numer_seryjny }}
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.narzedzia.id}/edit`">
            {{ item.narzedzia.waznosc_badan }}
          </Link>
        </td>
        <td class="border-t text-center">
          <Link class="flex items-center justify-center px-6 py-4" :href="`/narzedzia/${item.narzedzia.id}/edit`" tabindex="-1">
            {{ item.narzedzia_nb }}
          </Link>
        </td>
        <td class="border-t text-right px-6">
          <delete-button
            :href="`/budowy/${organization.id}/narzedzia/${item.id}/destroy`"
            confirm="Czy na pewno chcesz usunąć to narzędzie z budowy?"
          />
        </td>
      </tr>
      <tr v-if="toolsOnBuild.data.length === 0">
        <td class="px-6 py-4 border-t text-center text-gray-500" colspan="5">Brak sprzętu na budowie</td>
      </tr>
    </table>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'
import Icon from '@/Shared/Icon'
import Layout from '@/Shared/Layout'
import BudMenu from '@/Shared/BudMenu'
import FreeToolsList from '@/Pages/NarzedziaBudowa/FreeToolsList.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'


export default {
  components: {
    FreeToolsList,
    Icon,
    Link,
    BudMenu,
    DeleteButton,
  },
  layout: Layout,
  props: {
    organization: Object,
    toolsFree: Array,
    toolsOnBuild: Object,
  },
  remember: 'form',
  data() {
    return {
      budId: this.organization.id,
      org: {
        org: this.organization,
      },
      checkedValues: [],
      form: this.$inertia.form({
        start: '',
        end: '',
      }),
      output: '',
    }
  },
  methods: {
    find() {
      this.$inertia.post(`/budowy/${this.organization.id}/narzedzia/create`, this.form)
    },
  },
}
</script>
