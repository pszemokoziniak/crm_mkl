<template>
  <div>
    <Head :title="artykul.tytul" />

    <h1 class="mb-2 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/baza-wiedzy">Baza wiedzy</Link>
      <span class="text-indigo-400 font-medium">/</span> {{ artykul.tytul }}
    </h1>

    <div class="flex flex-wrap items-center gap-3 mb-6 text-sm text-gray-500">
      <span v-if="artykul.kategoria">{{ artykul.kategoria }}</span>
      <span v-if="artykul.tylko_admin" class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700">
        tylko admin
      </span>
      <span>zmieniony {{ artykul.zmieniony }}</span>
      <span v-if="admin" class="ml-auto flex items-center gap-4">
        <Link class="text-indigo-600 hover:text-indigo-800" :href="`/baza-wiedzy/${artykul.id}/edit`">Edytuj</Link>
        <button type="button" class="text-red-600 hover:text-red-800" @click="usun">Usuń</button>
      </span>
    </div>

    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <!-- Treść powstaje z markdowna po stronie serwera; surowy HTML jest
           z niej wycinany, więc nie da się tędy wstrzyknąć skryptu. -->
      <div class="artykul-tresc px-8 py-8" v-html="artykul.html" />
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    artykul: { type: Object, required: true },
  },
  computed: {
    admin() {
      return !!this.$page.props.permissions.admin
    },
  },
  methods: {
    usun() {
      if (confirm('Usunąć ten artykuł?')) {
        this.$inertia.delete(`/baza-wiedzy/${this.artykul.id}`)
      }
    },
  },
}
</script>

<!-- Bez scoped: style muszą sięgnąć treści wstawionej przez v-html, do której
     atrybuty zakresu się nie doklejają. Prefiks klasy trzyma je przy sobie. -->
<style>
.artykul-tresc {
  color: #334155;
  line-height: 1.65;
}
.artykul-tresc > *:first-child { margin-top: 0; }
.artykul-tresc > *:last-child { margin-bottom: 0; }
.artykul-tresc h1,
.artykul-tresc h2,
.artykul-tresc h3 {
  font-weight: 700;
  color: #1e293b;
  margin: 1.8em 0 0.6em;
  line-height: 1.3;
}
.artykul-tresc h1 { font-size: 1.5rem; }
.artykul-tresc h2 { font-size: 1.25rem; }
.artykul-tresc h3 { font-size: 1.05rem; }
.artykul-tresc p { margin: 0.8em 0; }
.artykul-tresc ul,
.artykul-tresc ol { margin: 0.8em 0; padding-left: 1.4em; }
.artykul-tresc ul { list-style: disc; }
.artykul-tresc ol { list-style: decimal; }
.artykul-tresc li { margin: 0.3em 0; }
.artykul-tresc a { color: #5661b3; text-decoration: underline; }
.artykul-tresc strong { font-weight: 600; color: #1e293b; }
.artykul-tresc hr { margin: 2em 0; border-top: 1px solid #e2e8f0; }
.artykul-tresc blockquote {
  margin: 1em 0;
  padding: 0.2em 1em;
  border-left: 3px solid #cbd5e1;
  color: #64748b;
}
.artykul-tresc code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.875em;
  background: #f1f5f9;
  padding: 0.15em 0.35em;
  border-radius: 3px;
}
/* Polecenia bywają długie — niech przewijają się w swoim kadrze,
   zamiast rozpychać całą stronę w bok. */
.artykul-tresc pre {
  margin: 1em 0;
  padding: 0.9em 1em;
  background: #1e293b;
  border-radius: 6px;
  overflow-x: auto;
}
.artykul-tresc pre code {
  background: none;
  padding: 0;
  color: #e2e8f0;
  white-space: pre;
}
.artykul-tresc table {
  width: 100%;
  margin: 1em 0;
  border-collapse: collapse;
  font-size: 0.9em;
}
.artykul-tresc th,
.artykul-tresc td {
  border: 1px solid #e2e8f0;
  padding: 0.5em 0.7em;
  text-align: left;
  vertical-align: top;
}
.artykul-tresc th { background: #f8fafc; font-weight: 600; }
</style>
