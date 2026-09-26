<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-6">
    <Head title="Link nieaktualny" />
    <div class="w-full max-w-sm bg-white rounded-xl shadow-sm p-6 text-center">
      <logo class="mx-auto mb-4 w-32" />
      <h1 class="text-lg font-bold text-gray-900">Ten link już nie działa</h1>
      <p class="mt-2 text-sm text-gray-600">Poproś kadry albo swojego kierownika o nowy link do strony HRM.</p>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Logo from '@/Shared/Logo'

export default {
  components: { Head, Logo },
}
</script>
