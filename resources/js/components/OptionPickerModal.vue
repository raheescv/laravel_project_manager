<template>
    <div v-if="show && product" class="posx-modal-backdrop" role="dialog" aria-modal="true"
        aria-labelledby="option-picker-title" @click.self="$emit('close')">
        <div class="posx-modal opx" @click.stop>
            <header class="opx-head">
                <div class="opx-ph">
                    <img v-if="product.photo" :src="product.photo" :alt="product.name">
                    <i v-else class="fa fa-cutlery"></i>
                </div>
                <div class="opx-title">
                    <h4 id="option-picker-title">{{ product.name }}</h4>
                    <p>{{ subtitle }}</p>
                </div>
                <div class="opx-price">{{ formatPrice(total) }}</div>
                <button type="button" class="opx-close" @click="$emit('close')" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </header>

            <div class="opx-body">
                <div v-if="requiredSlots.length > 1" class="opx-steps" aria-hidden="true">
                    <span v-for="slot in requiredSlots" :key="slot.key" :class="{ on: slot.done }"></span>
                </div>

                <section v-for="entry in visibleGroups" :key="entry.key" class="opx-group"
                    :class="{ nested: entry.depth > 0 }" :style="{ '--depth': entry.depth }">
                    <p class="opx-label">
                        <span>{{ entry.depth > 0 ? entry.parentName + ' · ' : '' }}{{ entry.group.name }}</span>
                        <em :class="{ req: entry.group.min > 0 }">{{ entry.group.rule }}</em>
                    </p>
                    <div class="opx-chips">
                        <button v-for="option in entry.group.items" :key="option.id" type="button" class="opx-chip"
                            :class="{ on: isPicked(entry, option), multi: isMulti(entry.group), full: isFull(entry) && !isPicked(entry, option) }"
                            :aria-pressed="isPicked(entry, option)" @click="toggle(entry, option)">
                            <span v-if="isMulti(entry.group)" class="opx-tick"><i v-if="isPicked(entry, option)" class="fa fa-check"></i></span>
                            <span class="opx-name">{{ option.name }}</span>
                            <small v-if="Number(option.price)">+{{ formatPrice(option.price) }}</small>
                        </button>
                    </div>
                </section>
            </div>

            <footer class="opx-foot">
                <div class="opx-sum">
                    <span>{{ summary || 'Pick the options' }}</span>
                    <b>{{ formatPrice(base) }}<template v-if="extra"> + {{ formatPrice(extra) }}</template></b>
                </div>
                <button type="button" class="opx-add" :disabled="!complete" @click="confirm">
                    <i class="fa fa-plus"></i> Add · {{ formatPrice(total) }}
                </button>
            </footer>
        </div>
    </div>
</template>

<script>
/**
 * POS option picker (Plan A, docs/product-options-plans-preview.html): the
 * product's option groups in order, each with its rule. A single-pick group
 * swaps its choice; a multi-pick group toggles up to its max. A picked option
 * with a follow-up group (Red Bull → Sugar) opens that group right under it.
 *
 * Click-and-go: when the last required choice is tapped and nothing on screen
 * is optional, the item is added at once. Otherwise "Add" confirms.
 *
 * Emits `pick` with { picks, lines, extra }: picks go to the server
 * (option_group_item_id + parent_option_group_item_id), lines are what the
 * cart shows under the item.
 */
export default {
    name: 'OptionPickerModal',
    emits: ['pick', 'close'],

    props: {
        show: { type: Boolean, default: false },
        product: { type: Object, default: null },
    },

    data() {
        // picked: "<parentOptionId>|<groupId>" => [option ids]
        return { picked: {} };
    },

    computed: {
        groups() {
            return this.product?.option_groups || [];
        },
        base() {
            return Number(this.product?.mrp) || 0;
        },
        /** Every group on screen: the product's own, then each picked option's follow-up right after its group. */
        visibleGroups() {
            const list = [];
            const walk = (groups, parentId, parentName, depth) => {
                groups.forEach(group => {
                    const entry = { key: `${parentId}|${group.id}`, group, parentId, parentName, depth };
                    list.push(entry);
                    group.items.forEach(option => {
                        if (option.next && this.isPicked(entry, option)) {
                            walk([option.next], option.id, option.name, depth + 1);
                        }
                    });
                });
            };
            walk(this.groups, 0, '', 0);
            return list;
        },
        requiredSlots() {
            return this.visibleGroups.filter(entry => entry.group.min > 0)
                .map(entry => ({ key: entry.key, done: this.count(entry) >= entry.group.min }));
        },
        complete() {
            return this.visibleGroups.every(entry => {
                const count = this.count(entry);
                return count >= entry.group.min && (entry.group.max === null || count <= entry.group.max);
            });
        },
        /** Anything left the cashier might still want to tap (an optional group, or room for more picks). */
        hasOpenChoices() {
            return this.visibleGroups.some(entry => entry.group.min === 0 || entry.group.max === null || entry.group.max > 1);
        },
        picks() {
            const rows = [];
            this.visibleGroups.forEach(entry => {
                entry.group.items.forEach(option => {
                    if (!this.isPicked(entry, option)) return;
                    rows.push({
                        option_group_item_id: option.id,
                        parent_option_group_item_id: entry.parentId || null,
                        group_name: entry.group.name,
                        option_name: option.name,
                        price: Number(option.price) || 0,
                        nested: entry.depth > 0,
                    });
                });
            });
            return rows;
        },
        extra() {
            return Math.round(this.picks.reduce((sum, pick) => sum + pick.price, 0) * 10000) / 10000;
        },
        total() {
            return this.base + this.extra;
        },
        summary() {
            return this.picks.map(pick => pick.option_name).join(' · ');
        },
        subtitle() {
            const required = this.requiredSlots.length;
            if (!required) return 'Optional extras';
            return `${required} ${required === 1 ? 'choice' : 'choices'}${this.hasOpenChoices ? '' : ' · then it drops into the cart'}`;
        },
    },

    watch: {
        show(open) {
            if (open) this.reset();
        },
        product() {
            this.reset();
        },
    },

    mounted() {
        window.addEventListener('keydown', this.onKey);
    },

    beforeUnmount() {
        window.removeEventListener('keydown', this.onKey);
    },

    methods: {
        /** Start from the options marked as default. */
        reset() {
            const picked = {};
            const seed = (groups, parentId) => {
                groups.forEach(group => {
                    const defaults = group.items.filter(option => option.is_default).slice(0, group.max ?? undefined);
                    if (defaults.length) {
                        picked[`${parentId}|${group.id}`] = defaults.map(option => option.id);
                        defaults.forEach(option => option.next && seed([option.next], option.id));
                    }
                });
            };
            seed(this.groups, 0);
            this.picked = picked;
        },
        isMulti(group) {
            return group.max === null || group.max > 1;
        },
        count(entry) {
            return (this.picked[entry.key] || []).length;
        },
        isPicked(entry, option) {
            return (this.picked[entry.key] || []).includes(option.id);
        },
        isFull(entry) {
            return this.isMulti(entry.group) && entry.group.max !== null && this.count(entry) >= entry.group.max;
        },
        toggle(entry, option) {
            const current = this.picked[entry.key] || [];
            let next;
            if (!this.isMulti(entry.group)) {
                // Single pick: tapping the chosen one again clears an optional group, keeps a required one.
                next = current.includes(option.id) ? (entry.group.min > 0 ? current : []) : [option.id];
            } else if (current.includes(option.id)) {
                next = current.filter(id => id !== option.id);
            } else {
                if (this.isFull(entry)) return;
                next = [...current, option.id];
            }
            this.picked = { ...this.picked, [entry.key]: next };
            this.dropOrphans();
            if (this.complete && !this.hasOpenChoices) this.confirm();
        },
        /** A follow-up group closes with its option: forget what was picked in it. */
        dropOrphans() {
            const live = new Set(this.visibleGroups.map(entry => entry.key));
            const kept = {};
            Object.keys(this.picked).forEach(key => {
                if (live.has(key)) kept[key] = this.picked[key];
            });
            this.picked = kept;
        },
        confirm() {
            if (!this.complete) return;
            this.$emit('pick', {
                picks: this.picks.map(({ option_group_item_id, parent_option_group_item_id }) => ({ option_group_item_id, parent_option_group_item_id })),
                lines: this.picks.map(({ group_name, option_name, price, nested }) => ({ group_name, option_name, price, nested })),
                extra: this.extra,
            });
        },
        formatPrice(price) {
            return (Number(price) || 0).toFixed(2);
        },
        onKey(event) {
            if (!this.show) return;
            if (event.key === 'Escape') this.$emit('close');
            if (event.key === 'Enter' && this.complete) this.confirm();
        },
    },
};
</script>

<style scoped>
/* Colours come from the .posx token layer (resources/css/pos-premium.css). */
.posx-modal.opx {
    width: 100%;
    max-width: 46rem;
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 92vh;
    max-height: 92dvh;
}

.opx-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--pos-line);
}

.opx-ph {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    flex: none;
    overflow: hidden;
    display: grid;
    place-items: center;
    font-size: 22px;
    color: var(--pos-muted);
    background: color-mix(in srgb, var(--pos-ink) 5%, var(--pos-panel));
}

.opx-ph img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.opx-title {
    flex: 1;
    min-width: 0;
}

.opx-title h4 {
    margin: 0;
    font-size: 19px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--pos-ink);
    overflow-wrap: anywhere;
}

.opx-title p {
    margin: 2px 0 0;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--pos-muted);
}

.opx-price {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--pos-ink);
}

.opx-close {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    border: 1px solid var(--pos-line-strong, var(--pos-line));
    background: var(--pos-panel);
    color: var(--pos-ink);
    font-size: 17px;
    flex: none;
}

.opx-body {
    padding: 16px 18px;
    display: grid;
    gap: 18px;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.opx-steps {
    display: flex;
    gap: 6px;
}

.opx-steps span {
    height: 5px;
    flex: 1;
    border-radius: 9px;
    background: var(--pos-line-strong, var(--pos-line));
}

.opx-steps span.on {
    background: var(--pos-pri);
}

.opx-group.nested {
    margin-left: calc(var(--depth) * 14px);
    margin-top: -8px;
    padding: 12px 14px;
    border-left: 3px solid var(--pos-acc, var(--pos-pri));
    border-radius: 0 14px 14px 0;
    background: color-mix(in srgb, var(--pos-acc, var(--pos-pri)) 9%, transparent);
}

.opx-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 9px;
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: var(--pos-muted);
}

.opx-label em {
    font-style: normal;
    text-transform: none;
    letter-spacing: 0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    background: color-mix(in srgb, var(--pos-ink) 6%, transparent);
    color: var(--pos-muted);
}

.opx-label em.req {
    background: var(--pos-danger-soft, rgba(194, 72, 63, .1));
    color: var(--pos-danger);
}

.opx-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.opx-chip {
    min-height: 52px;
    max-width: 100%;
    padding: 9px 16px;
    border-radius: 16px;
    border: 1.5px solid var(--pos-line-strong, var(--pos-line));
    background: var(--pos-panel);
    color: var(--pos-ink);
    font-size: 15px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-align: start;
    transition: background .12s, border-color .12s, color .12s;
}

.opx-name {
    overflow-wrap: anywhere;
}

.opx-chip small {
    font-size: 12px;
    font-weight: 700;
    color: var(--pos-muted);
    white-space: nowrap;
}

.opx-chip.on {
    background: var(--pos-pri);
    border-color: var(--pos-pri);
    color: var(--pos-on-pri, #fff);
}

.opx-chip.on small {
    color: inherit;
    opacity: .75;
}

.opx-chip.multi.on {
    background: color-mix(in srgb, var(--pos-pri) 8%, var(--pos-panel));
    color: var(--pos-ink);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--pos-pri) 22%, transparent);
}

.opx-chip.multi.on small {
    color: var(--pos-muted);
}

.opx-chip.full {
    opacity: .4;
}

.opx-tick {
    width: 18px;
    height: 18px;
    flex: none;
    border-radius: 6px;
    border: 1.5px solid var(--pos-line-strong, var(--pos-line));
    display: grid;
    place-items: center;
    font-size: 10px;
}

.opx-chip.multi.on .opx-tick {
    background: var(--pos-pri);
    border-color: var(--pos-pri);
    color: var(--pos-on-pri, #fff);
}

.opx-foot {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-top: 1px solid var(--pos-line);
    background: color-mix(in srgb, var(--pos-ink) 3%, var(--pos-panel));
}

.opx-sum {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    font-size: 13px;
    font-weight: 600;
    color: var(--pos-muted);
}

.opx-sum span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.opx-sum b {
    color: var(--pos-ink);
}

.opx-add {
    border: 0;
    border-radius: 14px;
    padding: 13px 20px;
    font-size: 15px;
    font-weight: 800;
    background: var(--pos-pri);
    color: var(--pos-on-pri, #fff);
    white-space: nowrap;
}

.opx-add:disabled {
    opacity: .4;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .opx-head { padding: 12px 14px; gap: 10px; }
    .opx-ph { width: 44px; height: 44px; border-radius: 12px; }
    .opx-price { font-size: 18px; }
    .opx-body { padding: 14px; }
    .opx-chip { min-height: 46px; font-size: 14px; padding: 8px 13px; }
    .opx-foot { padding: 12px 14px; }
}
</style>
