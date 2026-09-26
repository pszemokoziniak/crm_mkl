<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-6">
    <Head :title="ustawianie ? 'Ustaw PIN' : 'Podaj PIN'" />
    <form class="w-full max-w-sm bg-white rounded-xl shadow p-6" @submit.prevent="wyslij">
      <logo class="mx-auto mb-4 w-32" />
      <h1 class="text-lg font-bold text-gray-900 text-center">Cześć{{ imie ? `, ${imie}` : '' }}</h1>
      <p class="mt-1 mb-5 text-sm text-gray-600 text-center">
        {{ ustawianie ? 'Ustaw swój PIN (4–6 cyfr). Będziesz go podawać przy wejściu.' : 'Podaj swój PIN.' }}
      </p>
      <p v-if="zablokowany" class="mb-4 p-3 rounded bg-red-50 text-sm text-red-700">Za dużo prób. Spróbuj za kwadrans.</p>
      <label class="form-label" for="pin">PIN:</label>
      <input id="pin" v-model="form.pin" type="password" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" class="form-input w-full text-center text-2xl tracking-widest" :class="{ error: form.errors.pin }" maxlength="6" />
      <div v-if="form.errors.pin" class="form-error">{{ form.errors.pin }}</div>
      <template v-if="ustawianie">
        <label class="form-label mt-4" for="pin2">Powtórz PIN:</label>
        <input id="pin2" v-model="form.pin_confirmation" type="password" inputmode="numeric" pattern="[0-9]*" class="form-input w-full text-center text-2xl tracking-widest" :class="{ error: form.errors.pin_confirmation }" maxlength="6" />
        <div v-if="form.errors.pin_confirmation" class="form-error">{{ form.errors.pin_confirmation }}</div>
      </template>
      <button type="submit" class="btn-indigo w-full mt-6 py-3 text-base" :disabled="form.processing || zablokowany">
        {{ ustawianie ? 'Zapisz PIN i wejdź' : 'Wejdź' }}
      </button>
      <p class="mt-4 text-xs text-gray-400 text-center">Link jest tylko dla Ciebie — nie przesyłaj go dalej.</p>
    </form>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import Logo from '@/Shared/Logo'

export default {
  components: { Head, Logo },
  props: {
    token: String,
    imie: String,
    ustawianie: Boolean,
    zablokowany: Boolean,
  },
  data() {
    return { form: useForm({ pin: '', pin_confirmation: '' }) }
  },
  methods: {
    wyslij() {
      this.form.post(`/u/${this.token}/pin`, { onFinish: () => this.form.reset('pin', 'pin_confirmation') })
    },
  },
}
</script>
