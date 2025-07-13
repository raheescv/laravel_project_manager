<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all relative z-10 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 border-b border-slate-200">
                <div class="text-center">
                    <h2 class="text-xl font-bold text-slate-800">Your Feedback</h2>
                    <p class="text-slate-600 text-sm">We value your opinion! Please rate your experience.</p>
                </div>
            </div>

            <form @submit.prevent="submitFeedback">
                <div class="p-4">
                    <!-- Star Rating -->
                    <div class="star-rating mb-6">
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
                        <div class="text-center mt-2 text-sm text-slate-600">
                            {{ ratingDescription }}
                        </div>
                    </div>

                    <!-- Feedback Type -->
                    <div class="mb-4">
                        <label for="feedback_type" class="block font-semibold text-slate-700 mb-1">Feedback Type</label>
                        <select v-model="feedbackData.feedback_type" id="feedback_type"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="" disabled>Select Type</option>
                            <option v-for="(type, key) in feedbackTypes" :key="key" :value="key">{{ type }}</option>
                        </select>
                    </div>

                    <!-- Comments -->
                    <div class="mb-4">
                        <label for="comment" class="block font-semibold text-slate-700 mb-1">Your Comments</label>
                        <textarea v-model="feedbackData.feedback" id="comment" rows="4"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Please share your thoughts..."></textarea>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 flex justify-end gap-2 border-t border-slate-200">
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg text-white hover:from-green-600 hover:to-emerald-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        Submit Feedback
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
                feedback_type: null,
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
    color: #e4e4e4;
    transition: all 0.2s ease-in-out;
    filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.1));
    cursor: pointer;
}

.star-label:hover .star-icon {
    color: #ffc800;
    transform: scale(1.2);
    filter: drop-shadow(0 0 6px rgba(255, 200, 0, 0.6));
}

/* Highlight stars to the left of hovered star */
.star-label:hover~.star-label .star-icon {
    color: #e4e4e4;
    transform: scale(1);
}

.star-icon.active {
    color: #ffd700;
    transform: scale(1.1);
    filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.5));
}

/* Make active stars more prominent */
.star-icon.active:hover {
    transform: scale(1.3);
    filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.7));
}
</style>
