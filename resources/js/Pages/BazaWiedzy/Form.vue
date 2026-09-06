<template>
  <div class="flex flex-wrap -mb-8 -mr-6 p-8">
    <text-input v-model="form.tytul" :error="form.errors.tytul" class="pb-8 pr-6 w-full lg:w-2/3" label="Tytuł" />

    <div class="pb-8 pr-6 w-full lg:w-1/3">
      <label class="form-label" for="kategoria">Kategoria:</label>
      <!-- Lista podpowiedzi z już użytych kategorii, ale można wpisać nową. -->
      <input id="kategoria" v-model="form.kategoria" list="kategorie-uzyte" class="form-input" type="text" />
      <datalist id="kategorie-uzyte">
        <option v-for="k in kategorie" :key="k" :value="k" />
      </datalist>
      <div v-if="form.errors.kategoria" class="form-error">{{ form.errors.kategoria }}</div>
    </div>

    <textarea-input
      v-model="form.tresc"
      :error="form.errors.tresc"
      class="pb-4 pr-6 w-full"
      label="Treść"
      rows="22"
      style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.85rem"
    />
    <p class="pb-8 pr-6 w-full text-xs text-gray-500">
      Markdown: <code># Nagłówek</code>, <code>- lista</code>, <code>**pogrubienie**</code>,
      polecenia w potrójnych apostrofach <code>```</code>. Tabele i linki też działają.
    </p>

    <div class="pb-8 pr-6 w-full">
      <label class="flex items-start gap-2 text-sm text-gray-700">
        <input v-model="form.tylko_admin" type="checkbox" class="form-checkbox mt-0.5" />
        <span>
          Tylko dla administratora
          <span class="block text-xs text-gray-500">
            Do artykułów ze ścieżkami na serwerze i poleceniami — reszta firmy w ogóle ich nie zobaczy.
          </span>
        </span>
      </label>
    </div>

    <text-input
      v-model="form.kolejnosc"
      :error="form.errors.kolejnosc"
      class="pb-8 pr-6 w-full lg:w-1/4"
      label="Kolejność"
      type="number"
    />
  </div>
</template>

<script>
import TextInput from '@/Shared/TextInput'
import TextareaInput from '@/Shared/TextareaInput'

export default {
  components: { TextInput, TextareaInput },
  props: {
    form: { type: Object, required: true },
    kategorie: { type: Array, default: () => [] },
  },
}
</script>
