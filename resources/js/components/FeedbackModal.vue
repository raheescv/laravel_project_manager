<template>
    <div v-if="show" class="posx-modal-backdrop" role="dialog" aria-modal="true" @click.self="$emit('close')">
        <div class="posx-modal fbx" style="max-width: 30rem" @click.stop>

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
                    <div class="fbx-rating">
                        <div class="fbx-stars" role="radiogroup" aria-label="Rating" @mouseleave="hoverRating = 0">
                            <label v-for="i in 5" :key="i" class="fbx-star" :class="{ 'is-on': displayRating >= i }"
                                :title="ratingLabels[i]" @mouseenter="hoverRating = i">
                                <input type="radio" name="rating" :value="i" v-model="feedbackData.rating" class="fbx-sr">
                                <i class="fa fa-star"></i>
                            </label>
                        </div>
                        <div class="fbx-rating-caption" :class="{ 'is-empty': !displayRating }">
                            {{ displayRating ? ratingLabels[displayRating] : 'Tap a star to rate' }}
                        </div>
                    </div>

                    <div class="fbx-section">
                        <div class="posx-label fbx-heading">Feedback Type</div>
                        <div class="fbx-types" role="radiogroup" aria-label="Feedback type">
                            <label v-for="(type, key) in feedbackTypes" :key="key" class="fbx-type"
                                :class="[`fbx-type--${key}`, { 'is-selected': feedbackData.feedback_type === key }]">
                                <input type="radio" name="feedback_type" :value="key" v-model="feedbackData.feedback_type" class="fbx-sr">
                                <span class="fbx-type-icon"><i class="fa" :class="type.icon"></i></span>
                                <span class="fbx-type-name">{{ type.label }}</span>
                                <span class="fbx-type-check"><i class="fa fa-check"></i></span>
                            </label>
                        </div>
                    </div>

                    <div class="fbx-section">
                        <label for="comment" class="posx-label fbx-heading">
                            Your Comments
                            <span class="fbx-optional">Optional</span>
                        </label>
                        <textarea v-model="feedbackData.feedback" id="comment" rows="4" maxlength="500"
                            class="posx-field fbx-textarea" placeholder="Tell us what stood out…"></textarea>
                        <div class="fbx-count">{{ (feedbackData.feedback || '').length }}/500</div>
                    </div>
                </div>

                <div class="posx-modal-foot">
                    <button type="button" class="posx-btn posx-btn-ghost" @click="$emit('close')">
                        Cancel
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
            hoverRating: 0,
            feedbackData: {
                rating: 0,
                feedback_type: 'compliment',
                feedback: ''
            },
            ratingLabels: {
                1: 'Poor',
                2: 'Fair',
                3: 'Good',
                4: 'Very Good',
                5: 'Excellent'
            },
            feedbackTypes: {
                compliment: { label: 'Compliment', icon: 'fa-thumbs-o-up' },
                suggestion: { label: 'Suggestion', icon: 'fa-lightbulb-o' },
                complaint: { label: 'Complaint', icon: 'fa-exclamation-circle' }
            }
        }
    },

    watch: {
        sale: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    this.feedbackData = {
                        rating: newVal.rating || 0,
                        feedback_type: newVal.feedback_type || 'compliment',
                        feedback: newVal.feedback || ''
                    };
                }
            }
        }
    },

    computed: {
        displayRating() {
            return this.hoverRating || Number(this.feedbackData.rating) || 0;
        }
    },

    methods: {
        submitFeedback() {
            this.$emit('feedback-submitted', this.feedbackData);
            this.$emit('close');

            this.resetForm();

            if (this.$toast) {
                this.$toast.success('Thank you for your feedback!');
            }
        },

        resetForm() {
            this.hoverRating = 0;
            this.feedbackData = {
                rating: this.sale?.rating || 5,
                feedback_type: this.sale?.feedback_type || 'compliment',
                feedback: this.sale?.feedback || ''
            };
        }
    }
}
</script>

<style scoped>
.fbx-sr {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
    pointer-events: none;
}

.fbx-rating {
    text-align: center;
    padding: 18px 12px 14px;
    margin-bottom: 18px;
    border: 1px solid var(--pos-line);
    border-radius: var(--pos-radius);
    background: linear-gradient(180deg, var(--pos-pri-soft), transparent);
}

.fbx-stars {
    display: inline-flex;
    gap: 6px;
}

.fbx-star {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    margin: 0;
    border-radius: 50%;
    cursor: pointer;
    font-size: 30px;
    color: var(--pos-line-strong);
    transition: transform .15s ease, color .15s ease;
}

.fbx-star:hover {
    transform: scale(1.12);
}

.fbx-star.is-on {
    color: #f5b301;
    text-shadow: 0 2px 10px rgba(245, 179, 1, .35);
}

.fbx-star:has(.fbx-sr:focus-visible) {
    box-shadow: 0 0 0 3px var(--pos-pri-ring);
}

.fbx-rating-caption {
    margin-top: 6px;
    font-size: var(--pos-fs-body);
    font-weight: 700;
    letter-spacing: .02em;
    color: var(--pos-ink);
    min-height: 18px;
}

.fbx-rating-caption.is-empty {
    font-weight: 500;
    color: var(--pos-muted);
}

.fbx-section + .fbx-section {
    margin-top: 16px;
}

.fbx-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.fbx-optional {
    font-size: var(--pos-fs-micro);
    font-weight: 600;
    text-transform: none;
    letter-spacing: 0;
    color: var(--pos-muted);
}

.fbx-types {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.fbx-type {
    --fbx-tone: var(--pos-ok);
    --fbx-tone-soft: var(--pos-ok-soft);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 14px 8px 12px;
    border: 1.5px solid var(--pos-field-line);
    border-radius: var(--pos-radius);
    background: var(--pos-field);
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease, transform .15s ease;
}

.fbx-type--suggestion {
    --fbx-tone: var(--pos-warn);
    --fbx-tone-soft: var(--pos-warn-soft);
}

.fbx-type--complaint {
    --fbx-tone: var(--pos-danger);
    --fbx-tone-soft: var(--pos-danger-soft);
}

.fbx-type:hover {
    border-color: var(--fbx-tone);
    transform: translateY(-1px);
}

.fbx-type:has(.fbx-sr:focus-visible) {
    box-shadow: 0 0 0 3px var(--pos-pri-ring);
}

.fbx-type-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    font-size: 17px;
    color: var(--fbx-tone);
    background: var(--fbx-tone-soft);
    transition: background .15s ease, color .15s ease;
}

.fbx-type-name {
    font-size: var(--pos-fs-body);
    font-weight: 700;
    color: var(--pos-ink);
}

.fbx-type-check {
    position: absolute;
    top: 6px;
    right: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 9px;
    color: #fff;
    background: var(--fbx-tone);
    opacity: 0;
    transform: scale(.6);
    transition: opacity .15s ease, transform .15s ease;
}

.fbx-type.is-selected {
    border-color: var(--fbx-tone);
    background: var(--fbx-tone-soft);
    box-shadow: 0 6px 18px -12px var(--fbx-tone);
}

.fbx-type.is-selected .fbx-type-icon {
    color: #fff;
    background: var(--fbx-tone);
}

.fbx-type.is-selected .fbx-type-check {
    opacity: 1;
    transform: scale(1);
}

.fbx-textarea {
    min-height: 96px;
    padding-top: 10px;
    padding-bottom: 10px;
    resize: vertical;
}

.fbx-count {
    margin-top: 4px;
    text-align: right;
    font-size: var(--pos-fs-meta);
    color: var(--pos-muted);
}
</style>
