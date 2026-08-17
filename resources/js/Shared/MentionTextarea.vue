<template>
  <div :class="$attrs.class">
    <label v-if="label" class="form-label">{{ label }}:</label>
    <div class="relative">
      <textarea
        ref="textarea"
        :value="modelValue"
        :rows="rows"
        :placeholder="placeholder"
        class="form-textarea"
        :class="{ error: error }"
        @input="onInput"
        @paste="onPaste"
        @keydown.down="onArrow('down', $event)"
        @keydown.up="onArrow('up', $event)"
        @keydown.enter="onEnter"
        @keydown.tab="onEnter"
        @keydown.esc="closeMention"
        @blur="closeMention"
      />
      <div
        v-if="mentionOpen && matches.length"
        class="absolute left-0 z-20 mt-1 w-64 max-h-56 overflow-y-auto bg-white rounded-lg border border-gray-100 shadow-xl"
      >
        <button
          v-for="(user, index) in matches"
          :key="user.id"
          type="button"
          class="flex items-center gap-2 w-full px-3 py-2 text-left text-xs hover:bg-indigo-50 transition-colors"
          :class="{ 'bg-indigo-50': index === activeIndex }"
          @mousedown.prevent="pick(user)"
          @mouseenter="activeIndex = index"
        >
          <span class="flex items-center justify-center w-6 h-6 text-[10px] font-bold text-indigo-700 bg-indigo-100 rounded-full">
            {{ initials(user.label) }}
          </span>
          <span class="font-medium">{{ user.label }}</span>
        </button>
      </div>
    </div>
    <div v-if="error" class="form-error">{{ error }}</div>
  </div>
</template>

<script>
/**
 * Textarea z podpowiedziami po wpisaniu @.
 * Wzmianka zapisuje się jako @[Imię Nazwisko](user:ID) — po ID powiadomienie
 * trafia do właściwej osoby nawet po zmianie nazwiska.
 */
export default {
  name: 'MentionTextarea',
  inheritAttrs: false,
  props: {
    modelValue: { type: String, default: '' },
    users: { type: Array, default: () => [] },
    label: String,
    error: String,
    rows: { type: [String, Number], default: 3 },
    placeholder: { type: String, default: 'Napisz komentarz... wpisz @ żeby kogoś wywołać' },
  },
  emits: ['update:modelValue', 'paste-files'],
  data() {
    return {
      mentionOpen: false,
      query: '',
      mentionStart: -1,
      activeIndex: 0,
    }
  },
  computed: {
    matches() {
      const query = this.query.toLowerCase().trim()
      const list = query ? this.users.filter((user) => user.label.toLowerCase().includes(query)) : this.users

      return list.slice(0, 8)
    },
  },
  methods: {
    onInput(event) {
      this.$emit('update:modelValue', event.target.value)
      this.detectMention(event.target)
    },
    /** Obrazek wklejony w treść oddajemy rodzicowi — trafi do uploadera. */
    onPaste(event) {
      const files = Array.from(event.clipboardData?.files || [])

      if (files.length) {
        event.preventDefault()
        this.$emit('paste-files', files)
      }
    },
    detectMention(element) {
      const upToCursor = element.value.slice(0, element.selectionStart)
      const at = upToCursor.lastIndexOf('@')

      // @ musi stać na początku albo po spacji, a fraza być krótka i jednolinijkowa.
      if (at === -1 || (at > 0 && !/\s/.test(element.value[at - 1]))) {
        return this.closeMention()
      }

      const query = upToCursor.slice(at + 1)

      if (query.length > 30 || query.includes('\n')) {
        return this.closeMention()
      }

      this.query = query
      this.mentionStart = at
      this.mentionOpen = true
      this.activeIndex = 0
    },
    closeMention() {
      this.mentionOpen = false
      this.query = ''
      this.mentionStart = -1
    },
    onArrow(direction, event) {
      if (!this.mentionOpen || this.matches.length === 0) {
        return
      }

      event.preventDefault()
      const count = this.matches.length
      this.activeIndex = direction === 'down'
        ? (this.activeIndex + 1) % count
        : (this.activeIndex - 1 + count) % count
    },
    onEnter(event) {
      if (!this.mentionOpen || this.matches.length === 0) {
        return
      }

      event.preventDefault()
      this.pick(this.matches[this.activeIndex])
    },
    pick(user) {
      if (this.mentionStart === -1) {
        return
      }

      const before = this.modelValue.slice(0, this.mentionStart)
      const after = this.modelValue.slice(this.mentionStart + 1 + this.query.length)
      const mention = `@[${user.label}](user:${user.id}) `

      this.$emit('update:modelValue', before + mention + after)
      this.closeMention()

      this.$nextTick(() => {
        const element = this.$refs.textarea
        const position = (before + mention).length
        element.focus()
        element.setSelectionRange(position, position)
      })
    },
    initials(name) {
      return (name || '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
    },
    focus() {
      this.$refs.textarea.focus()
    },
  },
}
</script>
