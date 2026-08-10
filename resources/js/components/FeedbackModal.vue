<template>
    <div v-if="show" class="posx-modal-backdrop" role="dialog" aria-modal="true" @click.self="$emit('close')">
        <div class="posx-modal" style="max-width: 28rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-comment-o"></i>
                    <span>
                        Your Feedback
                        <span class="posx-modal-sub">Rate your experience</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="$emit('close')" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="submitFeedback" class="contents">
                <div class="posx-modal-body">
                    <!-- Stars -->
                    <div class="star-rating mb-4">
                        <div class="flex justify-center">
                            <template v-for="i in [1, 2, 3, 4, 5]" :key="i">
                                <input type="radio" :id="`star${i}`" name="rating" :value="i"
                                    v-model="feedbackData.rating" class="hidden">
                                <label :for="`star${i}`" class="star-label mx-2 cursor-pointer" :title="`${i} stars`">
                                    <i class="fa fa-star text-3xl star-icon"
                                        :class="{ 'active': feedbackData.rating >= i }"></i>
                                </label>
                            </template>
                        </div>
                        <div class="text-center mt-2 posx-muted text-xs font-semibold">{{ ratingDescription }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="feedback_type" class="posx-label mb-1"><i class="fa fa-tags"></i> Feedback Type</label>
                        <div class="relative">
                            <select v-model="feedbackData.feedback_type" id="feedback_type" class="posx-field">
                                <option value="" disabled>Select Type</option>
                                <option v-for="(type, key) in feedbackTypes" :key="key" :value="key">{{ type }}</option>
                            </select>
                            <i class="fa fa-angle-down posx-caret"></i>
                        </div>
                    </div>

                    <div>
                        <label for="comment" class="posx-label mb-1"><i class="fa fa-pencil"></i> Your Comments</label>
                        <textarea v-model="feedbackData.feedback" id="comment" rows="4"
                            class="posx-field" style="min-height: 88px; padding-top: 8px; padding-bottom: 8px"
                            placeholder="Please share your thoughts..."></textarea>
                    </div>
                </div>

                <div class="posx-modal-foot">
                    <button type="button" class="posx-btn posx-btn-ghost" @click="$emit('close')">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="posx-btn posx-btn-primary">
                        <i class="fa fa-paper-plane"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        show: {
            type: Boolean,
            default: false
        },
        sale: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            feedbackData: {
                rating: 0,
                feedback_type: 'compliment',
                feedback: ''
            },
            feedbackTypes: {
                'compliment': 'Compliment',
                'suggestion': 'Suggestion',
                'complaint': 'Complaint'
            }
        }
    },

    watch: {
        sale: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    // Initialize with existing feedback data if available, otherwise use defaults
                    this.feedbackData = {
                        rating: newVal.rating || 0,
                        feedback_type: newVal.feedback_type || null,
                        feedback: newVal.feedback || ''
                    };
                }
            }
        }
    },

    computed: {
        ratingDescription() {
            switch (this.feedbackData.rating) {
                case 1: return 'Poor';
                case 2: return 'Fair';
                case 3: return 'Good';
                case 4: return 'Very Good';
                case 5: return 'Excellent';
                default: return '';
            }
        }
    },

    methods: {
        submitFeedback() {
            // Emit the feedback data to the parent component
            this.$emit('feedback-submitted', this.feedbackData);
            this.$emit('close');

            // Reset form after submission
            this.resetForm();

            // Show success message using toast if available
            if (this.$toast) {
                this.$toast.success('Thank you for your feedback!');
            }
        },

        resetForm() {
            this.feedbackData = {
                rating: this.sale?.rating || 5,
                feedback_type: this.sale?.feedback_type || '',
                feedback: this.sale?.feedback || ''
            };
        }
    }
}
</script>

<style scoped>
.star-icon {
    color: var(--pos-line-strong);
    transition: all 0.2s ease-in-out;
    filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.1));
    cursor: pointer;
}

.star-label:hover .star-icon {
    color: var(--pos-acc);
    transform: scale(1.2);
    filter: drop-shadow(0 0 6px rgba(255, 200, 0, 0.6));
}

/* Highlight stars to the left of hovered star */
.star-label:hover~.star-label .star-icon {
    color: var(--pos-line-strong);
    transform: scale(1);
}

.star-icon.active {
    color: var(--pos-acc);
    transform: scale(1.1);
    filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
}

/* Make active stars more prominent */
.star-icon.active:hover {
    transform: scale(1.3);
    filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.7));
}
</style>
