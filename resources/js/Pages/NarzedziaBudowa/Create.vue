<template>
<!--  <Head :title="organization.name" />-->
  <BudMenu :budId="budId" />
  <h1 class="mb-8 text-3xl font-bold">
    <Link class="text-indigo-400 hover:text-indigo-600" href="/budowy">{{organization.name}}</Link>
    <span class="text-indigo-400 font-medium">/</span>
    <p class="text-base">Dodaj sprzęt do budowy</p>
  </h1>

<!--  <div class="max-w bg-white rounded-md shadow overflow-hidden">-->
<!--    <h3 class="font-medium text-xl  p-4">Znajdź wolny sprzęt</h3>-->
<!--    <form @submit.prevent="find()">-->
<!--      <div class="flex flex-wrap -mb-3 -mr-6 p-8">-->
<!--        <text-input type="date" v-model="form.start" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek pracy na budowie" />-->
<!--        <text-input type="date" v-model="form.end" :error="form.errors.end" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec pracy na budowie" />-->
<!--        <text-input type="hidden" value="@{{contact_id}}" v-model="form.contact_id" :error="form.errors.contact_id" />-->
<!--      </div>-->
<!--      <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">-->
<!--        <loading-button :loading="form.processing" class="btn-indigo" type="submit">Znajdź wolne narzędzia</loading-button>-->
<!--      </div>-->
<!--    </form>-->
<!--  </div>-->
  {{toolsFree}}
  <div v-if="toolsFree" class="max-w">
    <FreeToolsList :toolsFree="toolsFree" :organization="organization"/>
  </div>

  <div class="max-w bg-white rounded-md shadow overflow-hidden my-5">
    <h3 class="font-medium text-xl  p-4">Sprzęt na budowie</h3>
    <table class="w-full whitespace-nowrap">
      <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Nazwa</th>
        <th class="pb-4 pt-6 px-6">Daty</th>
        <th class="pb-4 pt-6 px-6" colspan="2">Ilość</th>
      </tr>
      <tr v-for="item in toolsOnBuild.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
        <td class="border-t">
          <Link class="flex items-center px-6 py-4 focus:text-indigo-500" href="#">
            {{ item.narzedzia.name }}
            <icon v-if="item.deleted_at" name="trash" class="flex-shrink-0 ml-2 w-3 h-3 fill-gray-400" />
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4" href="#" tabindex="-1">
            od: {{ item.start }}  do: {{ item.end }}
          </Link>
        </td>
        <td class="border-t">
          <Link class="flex items-center px-6 py-4" href="#" tabindex="-1">
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
// import TextInput from '@/Shared/TextInput'
// import LoadingButton from '@/Shared/LoadingButton'
import BudMenu from '@/Shared/BudMenu'
// import mapValues from 'lodash/mapValues'
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
    // toggleSeen: function() {
    //   this.toggle = !this.toggle;
    //   this.button.text = this.toggle ? 'Zamknij' : 'Dodaj pracownika';
    // },
  },
  mounted: function() {
    // console.log(this.start)
  },
}
</script>
