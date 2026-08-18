<template>
  <div>
    <Head title="Ustawienia" />
    <h1 class="mb-8 text-3xl font-bold">Ustawienia</h1>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="submit">
        <div class="p-8">
          <h2 class="mb-2 text-lg font-semibold text-gray-800">Prognoza pracowników</h2>
          <p class="mb-6 text-sm text-gray-500">
            Maksymalna liczba pracowników na osi pionowej wykresu prognozy. Nie zmienia danych —
            ustala tylko górną granicę skali, żeby słupki były czytelne.
          </p>
          <text-input
            v-model="form.prognoza_max_workers"
            type="number"
            min="1"
            :error="form.errors.prognoza_max_workers"
            class="w-full lg:w-1/2"
            label="Maksymalna liczba pracowników na wykresie"
          />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    TextInput,
    LoadingButton,
  },
  layout: Layout,
  props: {
    prognozaMaxWorkers: Number,
  },
  data() {
    return {
      form: this.$inertia.form({
        prognoza_max_workers: this.prognozaMaxWorkers,
      }),
    }
  },
  methods: {
    submit() {
      this.form.put('/ustawienia')
    },
  },
}
</script>
