<template>
  <Head title="Logowanie" />
  <div class="flex items-center justify-center p-6 min-h-screen bg-gradient-to-br from-indigo-900 via-indigo-800 to-blue-900">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-10 py-12">
          <div class="flex justify-center mb-8">
            <img src="/img/MKL-BAU.png" alt="logo" width="220" />
          </div>
          <h1 class="text-center text-2xl font-bold text-gray-800 tracking-tight">Panel Logowania</h1>
          <div class="mt-2 mx-auto w-12 border-b-4 border-indigo-500 rounded-full" />

          <form class="mt-10" @submit.prevent="login">
            <text-input
              v-model="form.email"
              :error="form.errors.email"
              class="mt-6"
              label="Email"
              type="email"
              autofocus
              autocapitalize="off"
            />
            <text-input
              v-model="form.password"
              :error="form.errors.password"
              class="mt-6"
              label="Hasło"
              type="password"
            />

            <div class="flex items-center justify-between mt-6">
              <label class="flex items-center select-none cursor-pointer" for="remember">
                <input id="remember" v-model="form.remember" class="mr-2 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition duration-150 ease-in-out" type="checkbox" />
                <span class="text-sm text-gray-600">Zapamiętaj mnie</span>
              </label>
            </div>

            <div v-if="$page.props.flash.error" class="mt-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-md animate-pulse">
              {{ $page.props.flash.error }}
            </div>

            <div class="mt-10">
              <loading-button :loading="form.processing" class="w-full btn-indigo py-3 text-lg justify-center shadow-lg hover:shadow-indigo-500/30 transition-all duration-200" type="submit">
                Zaloguj się
              </loading-button>
            </div>
          </form>
        </div>
      </div>
      <p class="text-center mt-8 text-indigo-100 text-sm font-medium opacity-80">
        &copy; {{ new Date().getFullYear() }} MKL-BAU. Wszystkie prawa zastrzeżone.
      </p>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: {
    Head,
    LoadingButton,
    TextInput,
  },
  data() {
    return {
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false,
      }),
    }
  },
  methods: {
    login() {
      this.form.post('/login')
    },
  },
}
</script>
