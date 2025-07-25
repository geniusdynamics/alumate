<template>
    <div class="relative">
        <div class="flex flex-wrap gap-2 p-2 border border-gray-300 rounded-md min-h-[42px] focus-within:ring-indigo-500 focus-within:border-indigo-500">
            <!-- Selected Skills -->
            <span
                v-for="(skill, index) in modelValue"
                :key="index"
                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800"
            >
                {{ skill }}
                <button
                    type="button"
                    @click="removeSkill(index)"
                    class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-600"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </span>

            <!-- Input Field -->
            <input
                ref="input"
                v-model="inputValue"
                type="text"
                :placeholder="modelValue.length === 0 ? placeholder : ''"
                class="flex-1 min-w-[120px] border-none outline-none focus:ring-0 p-0"
                @keydown.enter.prevent="addSkill"
                @keydown.comma.prevent="addSkill"
                @keydown.tab="addSkill"
                @keydown.backspace="handleBackspace"
            />
        </div>

        <!-- Suggestions -->
        <div v-if="suggestions.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
            <div
                v-for="suggestion in suggestions"
                :key="suggestion"
                @click="selectSuggestion(suggestion)"
                class="px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm"
            >
                {{ suggestion }}
            </div>
        </div>

        <!-- Help Text -->
        <p class="mt-1 text-xs text-gray-500">
            Type skills and press Enter, comma, or tab to add them
        </p>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: {
            type: Array,
            default: () => []
        },
        placeholder: {
            type: String,
            default: 'Add skills...'
        },
        maxSkills: {
            type: Number,
            default: 20
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            inputValue: '',
            suggestions: [],
            commonSkills: [
                'JavaScript', 'Python', 'Java', 'C++', 'HTML', 'CSS', 'React', 'Vue.js', 'Angular',
                'Node.js', 'PHP', 'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Git', 'Docker',
                'Project Management', 'Communication', 'Leadership', 'Problem Solving',
                'Data Analysis', 'Microsoft Office', 'Excel', 'PowerPoint', 'Photoshop',
                'Marketing', 'Sales', 'Customer Service', 'Accounting', 'Finance'
            ]
        }
    },

    watch: {
        inputValue(value) {
            this.updateSuggestions(value)
        }
    },

    methods: {
        addSkill() {
            const skill = this.inputValue.trim()
            if (skill && !this.modelValue.includes(skill) && this.modelValue.length < this.maxSkills) {
                const newSkills = [...this.modelValue, skill]
                this.$emit('update:modelValue', newSkills)
                this.inputValue = ''
                this.suggestions = []
            }
        },

        removeSkill(index) {
            const newSkills = [...this.modelValue]
            newSkills.splice(index, 1)
            this.$emit('update:modelValue', newSkills)
            this.$refs.input.focus()
        },

        selectSuggestion(suggestion) {
            if (!this.modelValue.includes(suggestion) && this.modelValue.length < this.maxSkills) {
                const newSkills = [...this.modelValue, suggestion]
                this.$emit('update:modelValue', newSkills)
                this.inputValue = ''
                this.suggestions = []
            }
        },

        handleBackspace() {
            if (this.inputValue === '' && this.modelValue.length > 0) {
                this.removeSkill(this.modelValue.length - 1)
            }
        },

        updateSuggestions(value) {
            if (value.length < 2) {
                this.suggestions = []
                return
            }

            this.suggestions = this.commonSkills
                .filter(skill => 
                    skill.toLowerCase().includes(value.toLowerCase()) &&
                    !this.modelValue.includes(skill)
                )
                .slice(0, 5)
        }
    }
}
</script>