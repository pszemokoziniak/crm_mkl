<template>
  <div class="flex items-center justify-center min-h-screen p-5 bg-indigo-100 min-w-screen">
    <div class="flex flex-col items-center">
      <div class="text-indigo-500 font-bold text-9xl">
        {{ status }}
      </div>

      <div class="font-medium text-center text-gray-800 md:text-xl lg:text-2xl mt-8">
        {{ title }}
      </div>

      <div class="text-center text-gray-500 md:text-lg mt-4">
        {{ description }}
      </div>

      <div class="mt-8">
        <Link href="/" class="px-6 py-2 text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition-colors">
          Wróć do strony głównej
        </Link>
      </div>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: {
    Link,
  },
  props: {
    status: Number,
  },
  computed: {
    title() {
      return {
        503: '503: Usługa niedostępna',
        500: '500: Błąd serwera',
        404: '404: Nie znaleziono strony',
        403: '403: Brak dostępu',
      }[this.status]
    },
    description() {
      return {
        503: 'Przepraszamy, trwają prace konserwacyjne. Spróbuj ponownie później.',
        500: 'Ups, coś poszło nie tak na naszych serwerach.',
        404: 'Przepraszamy, strona której szukasz nie istnieje.',
        403: 'Przepraszamy, nie masz uprawnień do przeglądania tej strony.',
      }[this.status]
    },
  },
}
</script>
