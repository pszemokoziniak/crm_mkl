<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Grupa stoi przed typem, bo tak wygląda magazyn: grupa zbiera modele,
         model dopiero sztuki. Zawęża też listę poniżej. -->
    <select-input v-model="grupaWybor" label="Nazwa grupy">
      <option value="">— wszystkie —</option>
      <option v-for="g in grupy" :key="g" :value="g">{{ g }}</option>
    </select-input>

    <select-input v-model="typWybor" :error="bledy.narzedzia_typ_id" label="Nazwa sprzętu (typ)">
      <option value="">— wybierz —</option>
      <option v-for="t in typyDoWyboru" :key="t.id" :value="t.id">{{ t.name }}</option>
      <option value="__new__">+ Nowy typ…</option>
    </select-input>

    <text-input
      v-if="typWybor === '__new__'"
      v-model="nazwaNowegoTypu"
      :error="bledy.new_typ_name"
      label="Nazwa nowego typu"
      placeholder="np. Manitou MT 1840"
    />

    <p class="md:col-span-2 -mt-2 text-sm text-gray-500">{{ podpowiedz }}</p>
  </div>
</template>

<script>
import SelectInput from '@/Shared/SelectInput'
import TextInput from '@/Shared/TextInput'

/**
 * Wybór grupy i typu sprzętu — jeden blok dla dodawania i edycji sztuki.
 *
 * Grupa jest widoczna zawsze, także przy istniejącym typie: bez tego nie
 * było na formularzu widać, gdzie sprzęt wyląduje w magazynie. Wybiera się
 * ją wyłącznie z listy — zakłada i nazywa w Ustawieniach → Grupy sprzętu,
 * żeby literówka nie tworzyła grupy obok istniejącej.
 */
export default {
  components: { SelectInput, TextInput },
  props: {
    modelValue: { type: [String, Number], default: '' },
    nowyTyp: { type: String, default: '' },
    nowaGrupa: { type: String, default: '' },
    typy: { type: Array, default: () => [] },
    grupy: { type: Array, default: () => [] },
    bledy: { type: Object, default: () => ({}) },
  },
  emits: ['update:modelValue', 'update:nowyTyp', 'update:nowaGrupa'],
  data() {
    return {
      typWybor: this.modelValue ?? '',
      grupaWybor: this.grupaWybranegoTypu(this.modelValue),
      nazwaNowegoTypu: this.nowyTyp,
    }
  },
  computed: {
    /** Przy wybranej grupie pokazujemy tylko jej modele — reszta tylko przeszkadza. */
    typyDoWyboru() {
      if (!this.grupaWybor) return this.typy

      return this.typy.filter((t) => t.grupa === this.grupaWybor)
    },
    podpowiedz() {
      if (this.typWybor === '__new__') {
        return this.grupaWybor
          ? `Nowy typ trafi do grupy „${this.grupaWybor}”.`
          : 'Wybierz grupę powyżej, inaczej nowy typ stanie w magazynie osobno.'
      }

      const typ = this.typy.find((t) => String(t.id) === String(this.typWybor))

      if (typ) {
        return typ.grupa
          ? `„${typ.name}” należy do grupy „${typ.grupa}”. Grupę zmienisz w Ustawieniach → Grupy sprzętu.`
          : `„${typ.name}” nie ma grupy — przypiszesz ją w Ustawieniach → Grupy sprzętu.`
      }

      return 'Wybór grupy zawęża listę typów obok. Grupy zakłada się w Ustawieniach → Grupy sprzętu.'
    },
  },
  watch: {
    typWybor(id) {
      this.$emit('update:modelValue', id)
    },
    nazwaNowegoTypu(nazwa) {
      this.$emit('update:nowyTyp', nazwa)
    },
    grupaWybor: {
      immediate: true,
      handler(grupa) {
        this.$emit('update:nowaGrupa', grupa)

        // Wybrany typ spoza nowej grupy zniknąłby z listy, a zostałby w danych —
        // formularz zapisywałby wtedy coś, czego nie widać na ekranie.
        if (!grupa) return

        const typ = this.typy.find((t) => String(t.id) === String(this.typWybor))
        if (typ && typ.grupa !== grupa) {
          this.typWybor = ''
        }
      },
    },
  },
  methods: {
    grupaWybranegoTypu(id) {
      const typ = this.typy.find((t) => String(t.id) === String(id))

      return (typ && typ.grupa) || ''
    },
  },
}
</script>
