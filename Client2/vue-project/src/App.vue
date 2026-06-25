<template>
  <main class="todo-app">
    <header class="app-head">
      <div>
        <h1>To-Do</h1>
        <p class="sub">A tiny app that uses everything in this roadmap.</p>
      </div>
      <span v-if="todos.length" class="badge">{{ remaining }} left</span>
    </header>

    <form class="add" @submit.prevent="addTodo">
      <input
        v-model.trim="draft"
        class="add-input"
        placeholder="What needs doing?"
        aria-label="New task"
      />
      <button class="add-btn" type="submit">Add</button>
    </form>

    <div v-if="todos.length" class="filters">
      <button
        v-for="f in filters"
        :key="f.value"
        class="chip"
        :class="{ active: filter === f.value }"
        @click="filter = f.value"
      >
        {{ f.label }}
      </button>
    </div>

    <ul class="list">
      <li v-for="todo in visibleTodos" :key="todo.id" class="item">
        <label class="check">
          <input type="checkbox" v-model="todo.done" />
          <span class="text" :class="{ done: todo.done }">{{ todo.text }}</span>
        </label>
        <button class="del" aria-label="Delete task" @click="removeTodo(todo.id)">✕</button>
      </li>
    </ul>

    <p v-if="todos.length === 0" class="empty">Nothing here yet — add your first task! ✨</p>

    <footer v-if="todos.length" class="app-foot">
      <span>{{ remaining }} of {{ todos.length }} active</span>
      <button v-if="hasDone" class="link" @click="clearCompleted">Clear completed</button>
    </footer>
  </main>
</template>

<script setup>
import { ref, computed } from 'vue'

let nextId = 1
const draft = ref('')
const filter = ref('all')
const todos = ref([
  { id: nextId++, text: 'Learn the basics of Vue', done: true },
  { id: nextId++, text: 'Build this to-do app', done: false },
  { id: nextId++, text: 'Show it off', done: false },
])

const filters = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'done', label: 'Done' },
]

const visibleTodos = computed(() => {
  if (filter.value === 'active') return todos.value.filter((t) => !t.done)
  if (filter.value === 'done') return todos.value.filter((t) => t.done)
  return todos.value
})

const remaining = computed(() => todos.value.filter((t) => !t.done).length)
const hasDone = computed(() => todos.value.some((t) => t.done))

function addTodo() {
  if (!draft.value) return
  todos.value.push({ id: nextId++, text: draft.value, done: false })
  draft.value = ''
}

function removeTodo(id) {
  todos.value = todos.value.filter((t) => t.id !== id)
}

function clearCompleted() {
  todos.value = todos.value.filter((t) => !t.done)
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.todo-app {
  max-width: 460px;
  margin: 48px auto;
  padding: 26px 26px 20px;
  background: #ffffff;
  border: 1px solid #ebedf1;
  border-radius: 18px;
  box-shadow: 0 12px 34px rgba(16, 24, 40, 0.09);
  font-family:
    system-ui,
    -apple-system,
    'Segoe UI',
    Roboto,
    sans-serif;
  color: #101620;
}

.app-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}
.app-head h1 {
  margin: 0;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.6px;
}
.sub {
  margin: 4px 0 0;
  font-size: 13px;
  color: #8b94a3;
}
.badge {
  flex: none;
  margin-top: 4px;
  font-size: 12.5px;
  font-weight: 700;
  color: #2f9e6e;
  background: #e9faf2;
  padding: 5px 11px;
  border-radius: 999px;
}

.add {
  display: flex;
  gap: 8px;
}
.add-input {
  flex: 1;
  min-width: 0;
  padding: 12px 14px;
  font-size: 15px;
  color: #101620;
  background: #f7f8fa;
  border: 1px solid #e3e6ec;
  border-radius: 11px;
  outline: none;
  transition:
    border-color 0.15s,
    box-shadow 0.15s,
    background 0.15s;
}
.add-input:focus {
  background: #ffffff;
  border-color: #42b883;
  box-shadow: 0 0 0 3px rgba(66, 184, 131, 0.18);
}
.add-btn {
  flex: none;
  padding: 12px 20px;
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  background: linear-gradient(135deg, #42b883, #2f9e6e);
  border: none;
  border-radius: 11px;
  cursor: pointer;
  transition:
    transform 0.12s,
    box-shadow 0.12s,
    opacity 0.12s;
}
.add-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(66, 184, 131, 0.38);
}
.add-btn:active {
  transform: translateY(0);
}

.filters {
  display: flex;
  gap: 7px;
  margin: 18px 0 2px;
}
.chip {
  padding: 6px 13px;
  font-size: 13px;
  font-weight: 600;
  color: #586172;
  background: #f2f3f6;
  border: 1px solid transparent;
  border-radius: 999px;
  cursor: pointer;
  transition: 0.13s;
}
.chip:hover {
  color: #101620;
}
.chip.active {
  color: #2f9e6e;
  background: #e9faf2;
  border-color: #bce7d3;
}

.list {
  list-style: none;
  margin: 10px 0 0;
  padding: 0;
}
.item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 6px;
  border-bottom: 1px solid #f1f2f5;
}
.item:last-child {
  border-bottom: none;
}
.check {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
  cursor: pointer;
}
.check input {
  flex: none;
  width: 19px;
  height: 19px;
  accent-color: #42b883;
  cursor: pointer;
}
.text {
  font-size: 15px;
  line-height: 1.4;
  word-break: break-word;
  transition: color 0.15s;
}
.text.done {
  color: #aab2bf;
  text-decoration: line-through;
}
.del {
  flex: none;
  width: 30px;
  height: 30px;
  border: none;
  background: transparent;
  color: #c4cad4;
  font-size: 15px;
  line-height: 1;
  border-radius: 9px;
  cursor: pointer;
  opacity: 0;
  transition: 0.15s;
}
.item:hover .del,
.del:focus-visible {
  opacity: 1;
}
.del:hover {
  color: #ef4444;
  background: #fdecec;
}

.empty {
  text-align: center;
  color: #98a1b0;
  padding: 30px 0 22px;
  margin: 6px 0 0;
  font-size: 14.5px;
}

.app-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid #f1f2f5;
  font-size: 13px;
  color: #8b94a3;
}
.link {
  border: none;
  background: none;
  padding: 0;
  font-size: 13px;
  color: #586172;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.link:hover {
  color: #ef4444;
}
</style>
