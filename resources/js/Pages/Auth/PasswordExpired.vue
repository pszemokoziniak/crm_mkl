<template>
  <Head title="Zmień hasło" />
  <div class="flex items-center justify-center p-6 min-h-screen bg-indigo-800">
    <div class="w-full max-w-md">
      <logo class="block mx-auto w-full max-w-xs fill-white" height="50" />
      <form class="mt-8 bg-white rounded-lg shadow-xl overflow-hidden" @submit.prevent="login">
        <div class="px-10 py-12">
          <h1 class="text-center text-3xl font-bold">Zmiana hasła</h1>
          <div class="mt-6 mx-auto w-24 border-b-2" />
          <text-input type="password" v-model="form.password" :error="form.errors.password" class="mt-6" label="Nowe hasło"/>
        </div>
        <div class="flex items-center px-10 py-4 bg-gray-100 border-t border-gray-100">
          <!-- Bez tego użytkownik nie ma jak wrócić do logowania: / i /login
               odsyłają go z powrotem na ten ekran. -->
          <Link class="text-sm text-gray-600 hover:text-gray-900 underline" href="/logout" method="delete" as="button" type="button">
            Wyloguj i zaloguj się na inne konto
          </Link>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zmień hasło</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Logo from '@/Shared/Logo'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    Logo,
    TextInput,
  },
  data() {
    return {
      form: this.$inertia.form({
        password: '',
      }),
    }
  },
  methods: {
    login() {
      this.form.post('/password/expired')
    },
  },
}
</script>
