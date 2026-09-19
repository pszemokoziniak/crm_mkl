<template>
  <!-- Zakwaterowani w pokoju (z datami) albo osoby, między które dzieli się koszt. -->
  <div class="text-sm">
    <div class="font-medium text-gray-800 mb-2">
      {{ koszt.nocleg ? `Zakwaterowani (miejsc: ${koszt.miejsc}, pokój ${koszt.od} – ${koszt.do})` : 'Osoby, między które dzieli się koszt' }}
    </div>
    <div v-for="(o, i) in lista" :key="i" class="flex flex-wrap items-center gap-2 mb-2">
      <select v-model="o.contact_id" class="form-select text-sm w-full sm:w-56">
        <option value="">— wybierz —</option>
        <option v-for="p in pracownicy" :key="p.id" :value="p.id">{{ p.nazwa }}</option>
      </select>
      <template v-if="koszt.nocleg">
        <input v-model="o.od" type="date" class="form-input text-sm w-full sm:w-40" :min="koszt.od" :max="koszt.do" />
        <input v-model="o.do" type="date" class="form-input text-sm w-full sm:w-40" :min="koszt.od" :max="koszt.do" />
      </template>
      <button type="button" class="text-red-600 text-xs" @click="lista.splice(i, 1)">usuń</button>
    </div>
    <p v-if="!koszt.nocleg && lista.length === 0" class="mb-2 text-xs text-gray-500">Bez wskazanych osób koszt dzieli się po dniach pobytu na budowie.</p>
    <div class="flex flex-wrap items-center gap-3">
      <button type="button" class="text-indigo-600" @click="dodaj">+ dodaj osobę</button>
      <loading-button :loading="zapisywanie" class="btn-indigo text-sm" type="button" @click="zapisz">Zapisz</loading-button>
      <button type="button" class="text-gray-600" @click="$emit('zamknij')">Anuluj</button>
    </div>
    <p v-if="blad" class="mt-2 text-sm text-red-700">{{ blad }}</p>
  </div>
</template>

<script>
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: { LoadingButton },
  props: {
    koszt: { type: Object, required: true },
    pracownicy: { type: Array, default: () => [] },
  },
  emits: ['zamknij'],
  data() {
    return {
      lista: this.koszt.osoby.map((o) => ({ contact_id: o.contact_id, od: o.od || '', do: o.do || '' })),
      zapisywanie: false,
      blad: null,
    }
  },
  methods: {
    dodaj() {
      this.lista.push({ contact_id: '', od: this.koszt.od || '', do: this.koszt.do || '' })
    },
    zapisz() {
      this.blad = null
      this.zapisywanie = true
      this.$inertia.put(
        `/koszty/${this.koszt.id}/osoby`,
        { osoby: this.lista.filter((o) => o.contact_id) },
        {
          preserveScroll: true,
          onSuccess: () => this.$emit('zamknij'),
          onError: (e) => { this.blad = Object.values(e).join(' ') },
          onFinish: () => { this.zapisywanie = false },
        },
      )
    },
  },
}
</script>
