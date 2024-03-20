<template>
<!--  <Head :title="organization.name" />-->
  <BudMenu :budId="budId" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{organization.name}}</Link>
    <span class="text-indigo-400 font-medium">/</span>
    <p class="text-base">Dodaj sprzęt do budowy</p>
  </h1>
  <div v-if="toolsFree" class="max-w">
    <FreeToolsList :toolsFree="toolsFree" :organization="organization"/>
  </div>

  <div class="max-w bg-white rounded-md shadow overflow-hidden my-5">
    <h3 class="font-medium text-xl  p-4">Sprzęt na budowie</h3>
    <table class="w-full whitespace-nowrap">
      <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Nazwa</th>
        <th class="pb-4 pt-6 px-6">Numer seryjny</th>
        <th class="pb-4 pt-6 px-6">Ważność badań</th>
        <th class="pb-4 pt-6 px-6" colspan="2">Ilość</th>
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
            <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="`/narzedzia/${item.narzedzia.id}/edit`">
            {{ item.narzedzia.waznosc_badan }}
            <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4" :href="`/narzedzia/${item.narzedzia.id}/edit`" tabindex="-1">
            {{ item.narzedzia_nb }}
          </Link>
        </td>
        <td class="w-px border-t">
          <Link class="flex items-center px-4" tabindex="-1" @click="destroy(organization.id, item.id)">
            <icon name="destroy" class="block w-6 h-6 fill-gray-400" />
          </Link>
        </td>
      </tr>
      <tr v-if="toolsFree === null">
        <td class="px-6 py-4 border-t" colspan="4">Nie znaleziono pracownika</td>
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


export default {
  components: {
    FreeToolsList,
    Icon,
    // LoadingButton,
    // TextInput,
    Link,
    BudMenu,
  },
  layout: Layout,
  props: {
    organization: Object,
    toolsFree: Object,
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
    destroy(organization, tool) {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/budowy/${organization}/narzedzia/${tool}/destroy`)
      }
    },

    find() {
    //   let currentObj = this
    //   axios.post(`/api/pracownicy/${this.organization.id}/find`,{
    //     start:this.form.start,
    //     end:this.form.end,
    //   })
    //     .then(function(response){
    //       console.log(response.data)
    //       currentObj.output=response.data
    //     })
    //     .catch(function(error){
    //       currentObj.output=error
    //     })
    // },

      // console.log(this.form.start);
      this.$inertia.post(`/budowy/${this.organization.id}/narzedzia/create`, this.form)

    },
  },
}
</script>
