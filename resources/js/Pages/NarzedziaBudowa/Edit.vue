<template>
  <div>
    <Head title="Edytuj ilość narzędzi" />
    <BudMenu :budId="organization.id" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" :href="`/budowy/${organization.id}/narzedzia`">Narzędzia na budowie</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ narzedzie.name }}
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <label class="form-label">Nazwa narzędzia:</label>
            <div class="form-input bg-gray-100">{{ narzedzie.name }}</div>
          </div>
          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <label class="form-label">Numer seryjny:</label>
            <div class="form-input bg-gray-100">{{ narzedzie.numer_seryjny || '-' }}</div>
          </div>

          <number-input
            v-model="form.narzedzia_nb"
            :error="form.errors.narzedzia_nb"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Ilość na tej budowie"
          />

          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <label class="form-label">Dostępne w magazynie (dodatkowo):</label>
            <div class="form-input bg-gray-100" :class="{'text-red-600': narzedzie.ilosc_magazyn < 0}">
              {{ narzedzie.ilosc_magazyn }}
            </div>
          </div>
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <delete-button
            :href="`/budowy/${organization.id}/narzedzia/${toolWorkDate.id}/destroy`"
            confirm="Czy na pewno chcesz usunąć to narzędzie z budowy?"
          >
            Usuń z budowy
          </delete-button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz zmiany</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import NumberInput from '@/Shared/NumberInput'
import LoadingButton from '@/Shared/LoadingButton'
import BudMenu from '@/Shared/BudMenu'
import DeleteButton from '@/Shared/DeleteButton.vue'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    NumberInput,
    BudMenu,
    DeleteButton,
  },
  layout: Layout,
  props: {
    organization: Object,
    toolWorkDate: Object,
    narzedzie: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        narzedzia_nb: this.toolWorkDate.narzedzia_nb,
      }),
    }
  },
  methods: {
    update() {
      this.form.put(`/budowy/${this.organization.id}/narzedzia/${this.toolWorkDate.id}`)
    },
  },
}
</script>
