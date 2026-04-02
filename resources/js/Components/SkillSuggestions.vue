<template>
    <div class="skill-suggestions">
        <div class="suggestions-header">
            <h3 class="text-lg font-semibold text-gray-900">Suggested Skills</h3>
            <p class="text-sm text-gray-600">Based on your career and connections</p>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="animate-pulse space-y-3">
                <div v-for="i in 5" :key="i" class="h-16 rounded-lg bg-gray-200"></div>
            </div>
        </div>

        <div v-else-if="suggestions.length === 0" class="empty-state">
            <Icon name="lightbulb" class="mx-auto mb-4 h-12 w-12 text-gray-400" />
            <p class="text-center text-gray-600">No skill suggestions available at the moment.</p>
        </div>

        <div v-else class="suggestions-list">
            <div v-for="suggestion in suggestions" :key="suggestion.id" class="suggestion-card">
                <div class="suggestion-content">
                    <div class="skill-info">
                        <h4 class="skill-name">{{ suggestion.name }}</h4>
                        <p class="skill-category">{{ suggestion.category }}</p>
                        <p class="skill-description" v-if="suggestion.description">
                            {{ suggestion.description }}
                        </p>
                    </div>

                    <div class="suggestion-meta">
                        <div class="suggestion-score">
                            <Icon name="trending-up" class="h-4 w-4 text-green-500" />
                            <span class="score-text">{{ suggestion.suggestion_score }} connections have this</span>
                        </div>

                        <div class="skill-stats" v-if="suggestion.stats">
                            <span class="stat-item">
                                <Icon name="users" class="h-4 w-4" />
                                {{ suggestion.stats.user_count }} professionals
                            </span>
                            <span class="stat-item">
                                <Icon name="briefcase" class="h-4 w-4" />
                                {{ suggestion.stats.job_count }} job postings
                            </span>
                        </div>
                    </div>

                    <div class="suggestion-reasons" v-if="suggestion.reasons && suggestion.reasons.length > 0">
                        <h5 class="reasons-title">Why this skill?</h5>
                        <ul class="reasons-list">
                            <li v-for="reason in suggestion.reasons" :key="reason" class="reason-item">
                                {{ reason }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="suggestion-actions">
                    <button @click="addSkill(suggestion)" class="btn-primary btn-sm" :disabled="addingSkills.includes(suggestion.id)">
                        {{ addingSkills.includes(suggestion.id) ? 'Adding...' : 'Add Skill' }}
                    </button>
                    <button @click="dismissSuggestion(suggestion)" class="btn-secondary btn-sm">Dismiss</button>
                </div>
            </div>
        </div>

        <div class="suggestions-footer" v-if="suggestions.length > 0">
            <button @click="refreshSuggestions" class="btn-secondary" :disabled="loading">
                <Icon name="refresh" class="h-4 w-4" />
                Refresh Suggestions
            </button>
        </div>

        <!-- Add Skill Modal -->
        <div v-if="showAddSkillModal" class="modal-overlay" @click="closeAddSkillModal">
            <div class="modal-content" @click.stop>
                <h3 class="modal-title">Add {{ selectedSkill?.name }}</h3>

                <div class="skill-preview">
                    <h4>{{ selectedSkill?.name }}</h4>
                    <p class="text-sm text-gray-600">{{ selectedSkill?.description }}</p>
                    <span class="category-tag">{{ selectedSkill?.category }}</span>
                </div>

                <form @submit.prevent="confirmAddSkill">
                    <div class="form-group">
                        <label>Your Proficiency Level</label>
                        <select v-model="skillForm.proficiency_level" class="form-select" required>
                            <option value="Beginner">Beginner - Just starting out</option>
                            <option value="Intermediate">Intermediate - Some experience</option>
                            <option value="Advanced">Advanced - Highly skilled</option>
                            <option value="Expert">Expert - Industry leader</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Years of Experience</label>
                        <input v-model.number="skillForm.years_experience" type="number" min="0" max="50" class="form-input" placeholder="0" />
                    </div>

                    <div class="proficiency-guide">
                        <h6 class="guide-title">Proficiency Guide:</h6>
                        <div class="guide-levels">
                            <div class="guide-level"><strong>Beginner:</strong> Learning the basics, limited practical experience</div>
                            <div class="guide-level"><strong>Intermediate:</strong> Can work independently, some real-world projects</div>
                            <div class="guide-level"><strong>Advanced:</strong> Deep expertise, can mentor others</div>
                            <div class="guide-level"><strong>Expert:</strong> Industry recognition, thought leadership</div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="closeAddSkillModal" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary" :disabled="loading">
                            {{ loading ? 'Adding...' : 'Add to Profile' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import Icon from './Icon.vue';

export default {
    name: 'SkillSuggestions',
    Components: {
        Icon,
    },
    data() {
        return {
            suggestions: [],
            loading: false,
            addingSkills: [],
            dismissedSkills: [],
            showAddSkillModal: false,
            selectedSkill: null,
            skillForm: {
                proficiency_level: 'Beginner',
                years_experience: 0,
            },
        };
    },
    mounted() {
        this.loadSuggestions();
    },
    methods: {
        async loadSuggestions() {
            this.loading = true;
            try {
                const response = await fetch('/api/skills/suggestions');
                const data = await response.json();

                // Filter out dismissed suggestions
                this.suggestions = data.suggestions.filter((skill) => !this.dismissedSkills.includes(skill.id));

                // Add mock reasons and stats for demo
                this.suggestions = this.suggestions.map((skill) => ({
                    ...skill,
                    reasons: this.generateReasons(skill),
                    stats: {
                        user_count: Math.floor(Math.random() * 1000) + 100,
                        job_count: Math.floor(Math.random() * 500) + 50,
                    },
                }));
            } catch (error) {
                console.error('Failed to load suggestions:', error);
            } finally {
                this.loading = false;
            }
        },
        addSkill(skill) {
            this.selectedSkill = skill;
            this.skillForm = {
                proficiency_level: 'Beginner',
                years_experience: 0,
            };
            this.showAddSkillModal = true;
        },
        async confirmAddSkill() {
            this.loading = true;
            try {
                const response = await fetch('/api/users/skills', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        skill_name: this.selectedSkill.name,
                        category: this.selectedSkill.category,
                        description: this.selectedSkill.description,
                        proficiency_level: this.skillForm.proficiency_level,
                        years_experience: this.skillForm.years_experience,
                    }),
                });

                if (response.ok) {
                    this.closeAddSkillModal();
                    // Remove from suggestions
                    this.suggestions = this.suggestions.filter((s) => s.id !== this.selectedSkill.id);
                    this.$emit('skill-added', this.selectedSkill);
                } else {
                    const error = await response.json();
                    // TODO-toast: alert(error.message || 'Failed to add skill');
                }
            } catch (error) {
                console.error('Failed to add skill:', error);
                // TODO-toast: alert('Failed to add skill');
            } finally {
                this.loading = false;
            }
        },
        dismissSuggestion(skill) {
            this.dismissedSkills.push(skill.id);
            this.suggestions = this.suggestions.filter((s) => s.id !== skill.id);

            // Store dismissed skills in localStorage
            localStorage.setItem('dismissedSkills', JSON.stringify(this.dismissedSkills));
        },
        async refreshSuggestions() {
            // Clear dismissed skills and reload
            this.dismissedSkills = [];
            localStorage.removeItem('dismissedSkills');
            await this.loadSuggestions();
        },
        closeAddSkillModal() {
            this.showAddSkillModal = false;
            this.selectedSkill = null;
        },
        generateReasons(skill) {
            const reasonTemplates = [
                `Popular in your industry (${skill.category})`,
                'Requested in recent job postings',
                'Common among your connections',
                'Trending skill in your field',
                'Complements your existing skills',
            ];

            // Return 2-3 random reasons
            const shuffled = reasonTemplates.sort(() => 0.5 - Math.random());
            return shuffled.slice(0, Math.floor(Math.random() * 2) + 2);
        },
    },
    created() {
        // Load dismissed skills from localStorage
        const dismissed = localStorage.getItem('dismissedSkills');
        if (dismissed) {
            this.dismissedSkills = JSON.parse(dismissed);
        }
    },
};
</script>

<style scoped>
.skill-suggestions {
    @apply space-y-6;
}

.suggestions-header {
    @apply mb-6 text-center;
}

.loading-state {
    @apply space-y-3;
}

.empty-state {
    @apply py-12 text-center;
}

.suggestions-list {
    @apply space-y-4;
}

.suggestion-card {
    @apply rounded-lg border bg-white p-6 transition-shadow hover:shadow-md;
}

.suggestion-content {
    @apply mb-4;
}

.skill-info {
    @apply mb-4;
}

.skill-name {
    @apply text-lg font-semibold text-gray-900;
}

.skill-category {
    @apply text-sm font-medium text-blue-600;
}

.skill-description {
    @apply mt-1 text-sm text-gray-600;
}

.suggestion-meta {
    @apply mb-4 space-y-2;
}

.suggestion-score {
    @apply flex items-center space-x-2 text-sm text-green-600;
}

.skill-stats {
    @apply flex space-x-4 text-sm text-gray-600;
}

.stat-item {
    @apply flex items-center space-x-1;
}

.suggestion-reasons {
    @apply rounded-lg bg-blue-50 p-3;
}

.reasons-title {
    @apply mb-2 text-sm font-medium text-blue-900;
}

.reasons-list {
    @apply space-y-1;
}

.reason-item {
    @apply text-sm text-blue-700;
}

.reason-item::before {
    content: '•';
    @apply mr-2 text-blue-500;
}

.suggestion-actions {
    @apply flex space-x-2 border-t pt-4;
}

.suggestions-footer {
    @apply text-center;
}

.skill-preview {
    @apply mb-6 rounded-lg bg-gray-50 p-4;
}

.category-tag {
    @apply mt-2 inline-block rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700;
}

.proficiency-guide {
    @apply mb-6 rounded-lg bg-yellow-50 p-4;
}

.guide-title {
    @apply mb-3 font-medium text-yellow-800;
}

.guide-levels {
    @apply space-y-2;
}

.guide-level {
    @apply text-sm text-yellow-700;
}
</style>
