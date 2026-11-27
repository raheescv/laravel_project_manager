<template>
    <div v-if="show && family" class="posx-modal-backdrop" role="dialog" aria-modal="true"
        aria-labelledby="variant-picker-title" @click.self="$emit('close')">
        <div class="posx-modal vsx" :class="{ solo: !colours.length }" @click.stop>
            <!-- Left: the product, in the chosen colour -->
            <aside class="vsx-stage">
                <div class="vsx-hero">
                    <img v-if="heroPhoto" :src="heroPhoto" :alt="family.name">
                    <span v-else-if="current" class="vsx-hero-dot" :class="{ none: !current.color }" :style="{ '--sw': current.hex }"></span>
                    <i v-else class="fa fa-cube vsx-hero-icon"></i>
                </div>
                <div class="vsx-caption">
                    <b>{{ captionTitle }}</b>
                    <span>{{ stockLine }}</span>
                </div>
                <div v-if="colours.length" class="vsx-thumbs">
                    <button v-for="option in colours" :key="'c-' + option.value" type="button" class="vsx-thumb"
                        :class="{ on: colour === option.value, out: option.stock <= 0 }"
                        :aria-pressed="colour === option.value" @click="pickColour(option.value)">
                        <span class="vsx-thumb-ph">
                            <img v-if="option.photo" :src="option.photo" :alt="option.name" loading="lazy">
                            <span v-else class="vsx-dot" :class="{ none: !option.value }" :style="{ '--sw': option.hex }"></span>
                        </span>
                        <span class="vsx-thumb-name">{{ option.name }}</span>
                    </button>
                </div>
            </aside>

            <!-- Right: the keys — one tap adds -->
            <section class="vsx-panel">
                <header class="vsx-head">
                    <div class="vsx-title">
                        <h4 id="variant-picker-title">{{ family.name }}</h4>
                        <p>
                            <span v-if="priceRange.from" class="vsx-from">from</span>
                            <b>{{ formatPrice(priceRange.min) }}</b>
                            <span class="vsx-sep">·</span>{{ family.variants.length }} items
                        </p>
                    </div>
                    <button type="button" class="vsx-close" @click="$emit('close')" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </header>

                <!-- Layer 1: the design / flavour / model, when sizes or colours sit under it -->
                <template v-if="showDesignRow">
                    <div class="vsx-label">
                        <span>{{ optionName }}</span>
                    </div>
                    <div class="vsx-designs" role="group" :aria-label="optionName">
                        <button v-for="option in designs" :key="'d-' + option.value" type="button" class="vsx-design"
                            :class="{ on: design === option.value, out: option.stock <= 0 }"
                            :aria-pressed="design === option.value" @click="pickDesign(option.value)">
                            {{ option.name }}
                        </button>
                    </div>
                </template>

                <div class="vsx-label">
                    <span>{{ keyLabel }}</span>
                    <em>Tap to add</em>
                </div>
                <div class="vsx-keys" :class="{ wide: wideKeys }">
                    <button v-for="key in keys" :key="key.id" type="button" class="vsx-key"
                        :class="{ out: key.variant && Number(key.variant.stock) <= 0 }"
                        :disabled="!key.variant" @click="$emit('pick', key.variant)">
                        <span v-if="keyKind === 'colour'" class="vsx-dot" :class="{ none: !key.hex }" :style="{ '--sw': key.hex }"></span>
                        <b>{{ key.label }}</b>
                        <small v-if="!key.variant">{{ current ? 'Not in ' + current.name : '—' }}</small>
                        <small v-else-if="Number(key.variant.stock) <= 0">Out of stock</small>
                        <small v-else>{{ Number(key.variant.stock) }} left<template v-if="priceRange.from"> · {{ formatPrice(key.variant.mrp) }}</template></small>
                    </button>
                </div>

                <footer class="vsx-foot">
                    <i class="fa fa-barcode"></i>
                    Scanning a barcode adds that item directly
                </footer>
            </section>
        </div>
    </div>
</template>

<script>
/**
 * POS size / colour picker for a product family — the "Studio" layout
 * (preview: docs/variant-picker-premium-preview.html). The left side shows the
 * product in the chosen colour with every colour as a photo thumbnail; the
 * right side is a size keypad for that colour. Tapping a size adds the item:
 * click-and-go, no Save. A family with sizes only skips the colours; one with
 * colours only turns the keypad into colour keys.
 *
 * Families can have three layers: a design / flavour / model (`design`, named
 * by the family's `option_name`), then size, then colour. With sizes, the
 * designs sit in a row above the keypad and narrow it; without sizes (a drink
 * in flavours) the designs ARE the keys.
 */
const plural = word => {
    if (/[^aeiou]y$/i.test(word)) return word.slice(0, -1) + 'ies';
    if (/(s|x|z|ch|sh)$/i.test(word)) return word + 'es';
    return word + 's';
};

const sizeOrder = (a, b) => {
    const numA = /^\d+(\.\d+)?$/.test(a);
    const numB = /^\d+(\.\d+)?$/.test(b);
    if (numA && numB) return parseFloat(a) - parseFloat(b);
    if (numA !== numB) return numA ? -1 : 1;
    return a.localeCompare(b, undefined, { numeric: true });
};

export default {
    name: 'VariantPickerModal',
    emits: ['pick', 'close'],

    props: {
        show: { type: Boolean, default: false },
        family: { type: Object, default: null },
    },

    data() {
        return { colour: null, design: null };
    },

    computed: {
        variants() {
            return this.family?.variants || [];
        },
        optionName() {
            return this.family?.option_name || this.variants.find(v => v.option_name)?.option_name || 'Design';
        },
        /** What the family calls its second layer: "Size", or "Flavour" for a Base × Flavour pack. */
        sizeName() {
            return this.family?.size_name || this.variants.find(v => v.size_name)?.size_name || 'Size';
        },
        /** Designs, colours and long size names ("Small", "Free size") need the wide keys. */
        wideKeys() {
            if (this.keyKind !== 'size') return true;
            return this.sizes.some(size => size.length > 4);
        },
        /** One entry per design / flavour, with its stock, in catalog order. */
        designs() {
            const seen = new Map();
            this.variants.forEach(v => {
                const value = this.designOf(v);
                if (!value) return;
                const option = seen.get(value) || { value, name: value, stock: 0 };
                option.stock += Number(v.stock) || 0;
                seen.set(value, option);
            });
            return [...seen.values()];
        },
        /** Sizes sit under the designs, so the designs get a row of their own. */
        showDesignRow() {
            return this.designs.length > 0 && this.sizes.length > 0;
        },
        /** The items under the chosen design (all of them when designs are the keys or absent). */
        scoped() {
            if (!this.showDesignRow || this.design === null) return this.variants;
            return this.variants.filter(v => this.designOf(v) === this.design);
        },
        captionTitle() {
            const parts = [this.showDesignRow ? this.design : null, this.current ? this.current.name : null].filter(Boolean);
            return parts.length ? parts.join(' · ') : this.family.name;
        },
        sizes() {
            return [...new Set(this.variants.map(v => this.sizeOf(v)).filter(Boolean))].sort(sizeOrder);
        },
        /** One entry per colour, with its stock and the first photo taken of it. */
        colours() {
            if (!this.variants.some(v => v.color)) return [];
            const seen = new Map();
            this.scoped.forEach(v => {
                const value = this.colourKey(v);
                const option = seen.get(value) || { value, name: value ? v.color_name : 'No colour', hex: v.color_hex, stock: 0, photo: null };
                option.stock += Number(v.stock) || 0;
                option.photo = option.photo || v.photo || null;
                seen.set(value, option);
            });
            return [...seen.values()];
        },
        current() {
            return this.colours.find(option => option.value === this.colour) || null;
        },
        /** The chosen colour's items; every item when the family has no colours. */
        inColour() {
            return this.current ? this.scoped.filter(v => this.colourKey(v) === this.colour) : this.scoped;
        },
        heroPhoto() {
            if (this.current) return this.current.photo;
            return this.scoped.find(v => v.photo)?.photo || this.variants.find(v => v.photo)?.photo || null;
        },
        stockLine() {
            const stock = this.inColour.reduce((total, v) => total + (Number(v.stock) || 0), 0);
            const sizes = new Set(this.inColour.map(v => this.sizeOf(v)).filter(Boolean)).size;
            const size = this.sizeName.toLowerCase();
            if (sizes) return `${stock} in stock across ${sizes} ${sizes === 1 ? size : plural(size)}`;
            const designs = new Set(this.inColour.map(v => this.designOf(v)).filter(Boolean)).size;
            const option = this.optionName.toLowerCase();
            return designs ? `${stock} in stock across ${designs} ${designs === 1 ? option : plural(option)}` : `${stock} in stock`;
        },
        priceRange() {
            const prices = this.inColour.map(v => Number(v.mrp) || 0);
            const min = Math.min(...prices);
            return { min, from: Math.max(...prices) > min };
        },
        keyKind() {
            if (this.sizes.length) return 'size';
            if (this.designs.length) return 'design';
            return this.colours.length ? 'colour' : 'item';
        },
        keyLabel() {
            const where = this.current ? ` in ${this.current.name}` : '';
            if (this.keyKind === 'size') return `${this.sizeName}${where}`;
            if (this.keyKind === 'design') return `${this.optionName}${where}`;
            return this.keyKind === 'colour' ? 'Colour' : 'Items';
        },
        /** The keypad: every size (or flavour, design…) of the family, ghosted where this colour has none. */
        keys() {
            if (this.keyKind === 'size') {
                return this.sizes.map(size => ({
                    id: 's-' + size,
                    label: size,
                    variant: this.best(this.inColour.filter(v => this.sizeOf(v) === size)),
                }));
            }
            if (this.keyKind === 'design') {
                return this.designs.map(option => ({
                    id: 'd-' + option.value,
                    label: option.name,
                    variant: this.best(this.inColour.filter(v => this.designOf(v) === option.value)),
                }));
            }
            if (this.keyKind === 'colour') {
                return this.colours.map(option => ({
                    id: 'c-' + option.value,
                    label: option.name,
                    hex: option.value ? option.hex : null,
                    variant: this.best(this.variants.filter(v => this.colourKey(v) === option.value)),
                }));
            }
            return this.variants.map(v => ({ id: 'v-' + v.id, label: v.barcode || v.name, variant: v }));
        },
    },

    watch: {
        show(open) {
            if (open) this.reset();
        },
        family() {
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
        colourKey(variant) {
            return (variant.color ?? '').toString();
        },
        sizeOf(variant) {
            return (variant.size ?? '').toString().trim();
        },
        designOf(variant) {
            return (variant.design ?? '').toString().trim();
        },
        /** Of several stock rows for one size and colour, the one holding the most. */
        best(rows) {
            return rows.reduce((top, v) => (!top || Number(v.stock) > Number(top.stock) ? v : top), null);
        },
        /** Open on the first design, then the first colour of it, that has stock. */
        reset() {
            const designs = this.designs;
            this.design = designs.length ? (designs.find(option => option.stock > 0) || designs[0]).value : null;
            this.resetColour();
        },
        resetColour() {
            const colours = this.colours;
            this.colour = colours.length ? (colours.find(option => option.stock > 0) || colours[0]).value : null;
        },
        pickColour(value) {
            this.colour = value;
        },
        /** A new design keeps the colour when it comes in it, else opens on its first colour in stock. */
        pickDesign(value) {
            this.design = value;
            if (!this.colours.some(option => option.value === this.colour)) this.resetColour();
        },
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2) : '0.00';
        },
        onKey(event) {
            if (this.show && event.key === 'Escape') this.$emit('close');
        },
    },
};
</script>

<style scoped>
/* Colours come from the .posx token layer (resources/css/pos-premium.css). */
.posx-modal.vsx {
    --vsx-photo: color-mix(in srgb, var(--pos-ink) 4%, var(--pos-panel));
    width: 100%;
    max-width: 60rem;
    padding: 0;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr);
    max-height: 92vh;
    max-height: 92dvh;
}

.posx-modal.vsx.solo {
    grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
}

/* ---- left: the product ---- */
.vsx-stage {
    background: var(--vsx-photo);
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    min-height: 0;
    max-height: 92vh;
    max-height: 92dvh;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

.vsx-hero {
    flex: 1;
    min-height: 200px;
    display: grid;
    place-items: center;
}

.vsx-hero img {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 16px;
    filter: drop-shadow(0 18px 18px rgba(0, 0, 0, .14));
}

.vsx-hero-dot {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: var(--sw, #9ca3af);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .15), 0 18px 30px -18px rgba(0, 0, 0, .4);
}

.vsx-hero-icon {
    font-size: 72px;
    color: var(--pos-muted);
}

.vsx-caption {
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--pos-ink-2);
}

.vsx-caption b {
    display: block;
    font-size: 22px;
    font-weight: 900;
    color: var(--pos-ink);
    letter-spacing: -.02em;
}

.vsx-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
    gap: 10px;
}

.vsx-thumb {
    border: 2px solid transparent;
    background: var(--pos-panel);
    border-radius: 16px;
    padding: 6px 6px 8px;
    color: var(--pos-ink);
    transition: border-color .15s, transform .1s;
    min-height: 104px;
}

.vsx-thumb:active,
.vsx-key:active:not(:disabled) {
    transform: scale(.97);
}

.vsx-thumb-ph {
    display: grid;
    place-items: center;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
}

.vsx-thumb-ph img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vsx-thumb .vsx-dot {
    width: 44px;
    height: 44px;
}

.vsx-thumb-name {
    display: block;
    margin-top: 5px;
    font-size: 12.5px;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vsx-thumb.on {
    border-color: var(--pos-pri);
    box-shadow: 0 8px 18px -10px var(--pos-pri);
}

.vsx-thumb.out .vsx-thumb-name {
    color: var(--pos-muted);
    text-decoration: line-through;
}

/* ---- right: the keypad ---- */
.vsx-panel {
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-height: 0;
    max-height: 92vh;
    max-height: 92dvh;
    overflow-y: auto;
    overscroll-behavior: contain;
    background: var(--pos-panel);
}

.vsx-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.vsx-title {
    flex: 1;
    min-width: 0;
}

.vsx-title h4 {
    margin: 0;
    font-size: 22px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--pos-ink);
}

.vsx-title p {
    margin: 4px 0 0;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--pos-ink-2);
}

.vsx-title p b {
    color: var(--pos-ink);
    font-size: 16px;
    font-weight: 800;
}

.vsx-from {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-right: 4px;
}

.vsx-sep {
    margin: 0 6px;
}

.vsx-close {
    width: 52px;
    height: 52px;
    flex: none;
    border-radius: 16px;
    border: 1px solid var(--pos-line-strong, var(--pos-line));
    background: var(--pos-panel-2, var(--pos-panel));
    color: var(--pos-ink);
    font-size: 18px;
}

.vsx-label {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    font-size: var(--pos-fs-label, 12px);
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--pos-muted);
}

.vsx-label em {
    font-style: normal;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 600;
}

.vsx-designs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 18px;
}

.vsx-design {
    min-height: 46px;
    max-width: 100%;
    padding: 8px 18px;
    border-radius: 99px;
    border: 1.5px solid var(--pos-line-strong, var(--pos-line));
    background: var(--pos-panel);
    color: var(--pos-ink);
    font-size: 15px;
    font-weight: 800;
    overflow-wrap: anywhere;
    transition: background .12s, border-color .12s, color .12s;
}

.vsx-design.out {
    color: var(--pos-muted);
}

.vsx-design.on {
    background: var(--pos-pri);
    border-color: var(--pos-pri);
    color: var(--pos-on-pri, #fff);
}

.vsx-keys {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 10px;
}

.vsx-keys.wide {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}

.vsx-key {
    min-width: 0;
    min-height: 84px;
    border-radius: 18px;
    border: 1.5px solid var(--pos-line-strong, var(--pos-line));
    background: var(--pos-panel);
    color: var(--pos-ink);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 8px;
    transition: background .12s, border-color .12s, color .12s, transform .1s;
}

.vsx-key b {
    font-size: 26px;
    font-weight: 900;
    letter-spacing: -.02em;
    line-height: 1.05;
    max-width: 100%;
    text-align: center;
    overflow-wrap: anywhere;
}

.vsx-keys.wide .vsx-key b {
    font-size: 17px;
    font-weight: 800;
    letter-spacing: 0;
}

.vsx-key small {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--pos-ok);
}

.vsx-key.out small {
    color: var(--pos-danger);
}

.vsx-key:hover:not(:disabled) {
    background: var(--pos-pri);
    border-color: var(--pos-pri);
    color: var(--pos-on-pri, #fff);
}

.vsx-key:hover:not(:disabled) small {
    color: inherit;
    opacity: .85;
}

.vsx-key:disabled {
    opacity: .3;
    border-style: dashed;
    cursor: not-allowed;
}

.vsx-key:disabled small {
    color: var(--pos-muted);
}

.vsx-foot {
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid var(--pos-line);
    font-size: 12.5px;
    font-weight: 600;
    color: var(--pos-muted);
    display: flex;
    gap: 8px;
    align-items: center;
}

.vsx-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    flex: none;
    display: inline-block;
    background: var(--sw, #9ca3af);
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .18);
}

.vsx-keys.wide .vsx-dot {
    width: 30px;
    height: 30px;
}

.vsx-dot.none {
    background: linear-gradient(135deg, transparent 44%, var(--pos-muted) 44% 56%, transparent 56%);
    box-shadow: inset 0 0 0 1.5px var(--pos-muted);
}

/* ---- tablet: same two columns, denser ---- */
@media (max-width: 1100px) {
    .vsx-stage,
    .vsx-panel {
        padding: 18px;
    }

    .vsx-hero {
        flex: none;
        min-height: 150px;
    }

    .vsx-hero img {
        max-height: 200px;
    }

    .vsx-hero-dot {
        width: 110px;
        height: 110px;
    }

    .vsx-caption b,
    .vsx-title h4 {
        font-size: 19px;
    }

    .vsx-thumbs {
        grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
        gap: 8px;
    }

    .vsx-thumb {
        min-height: 0;
        border-radius: 14px;
    }

    .vsx-thumb .vsx-dot {
        width: 34px;
        height: 34px;
    }

    .vsx-thumb-name {
        font-size: 11.5px;
    }

    .vsx-keys {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 8px;
    }

    .vsx-keys.wide {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    }

    .vsx-key {
        min-height: 72px;
        border-radius: 16px;
    }

    .vsx-key b {
        font-size: 22px;
    }

    .vsx-close {
        width: 46px;
        height: 46px;
    }
}

/*
 * ---- phone: one scrolling column ----
 * The panel dissolves (display: contents) so its header can sit above the
 * product and stay pinned while the colours and sizes scroll beneath it.
 */
@media (max-width: 760px) {
    .posx-modal.vsx,
    .posx-modal.vsx.solo {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: calc(100dvh - 24px);
        overflow-y: auto;
        overscroll-behavior: contain;
        background: var(--pos-panel);
    }

    .vsx-panel {
        display: contents;
    }

    .vsx-head {
        order: 0;
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 14px 14px 10px;
        background: var(--pos-panel);
        border-bottom: 1px solid var(--pos-line);
    }

    .vsx-title h4 {
        font-size: 18px;
    }

    .vsx-close {
        width: 44px;
        height: 44px;
        border-radius: 14px;
    }

    .vsx-stage {
        order: 1;
        display: grid;
        grid-template-columns: 84px minmax(0, 1fr);
        align-items: center;
        gap: 10px 14px;
        margin: 0 14px;
        padding: 10px;
        border-radius: 16px;
        max-height: none;
        overflow: visible;
    }

    .vsx-hero {
        min-height: 0;
        width: 84px;
        height: 84px;
    }

    .vsx-hero img {
        width: 100%;
        height: 100%;
        max-height: none;
        border-radius: 12px;
        filter: none;
    }

    .vsx-hero-dot {
        width: 60px;
        height: 60px;
    }

    .vsx-hero-icon {
        font-size: 40px;
    }

    .vsx-caption {
        text-align: left;
        font-size: 12.5px;
    }

    .vsx-caption b {
        font-size: 17px;
    }

    .vsx-thumbs {
        grid-column: 1 / -1;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scroll-snap-type: x proximity;
        scrollbar-width: none;
        margin: 0 -10px;
        padding: 2px 10px 4px;
    }

    .vsx-thumbs::-webkit-scrollbar {
        display: none;
    }

    .vsx-thumb {
        flex: 0 0 72px;
        scroll-snap-align: start;
        padding: 4px 4px 6px;
        border-radius: 12px;
    }

    .vsx-thumb-ph {
        border-radius: 9px;
    }

    .vsx-thumb .vsx-dot {
        width: 30px;
        height: 30px;
    }

    .vsx-thumb-name {
        font-size: 11px;
    }

    .vsx-label {
        order: 2;
        padding: 0 14px;
    }

    .vsx-keys {
        order: 3;
        padding: 0 14px;
        grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
    }

    .vsx-keys.wide {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }

    .vsx-key {
        min-height: 64px;
        border-radius: 14px;
        padding: 6px;
    }

    .vsx-key b {
        font-size: 20px;
    }

    .vsx-keys.wide .vsx-key b {
        font-size: 15px;
    }

    .vsx-key small {
        font-size: 10.5px;
        text-align: center;
    }

    .vsx-foot {
        order: 4;
        margin: 0 14px 14px;
        padding-top: 12px;
    }
}

/* ---- short landscape screens: keep the hero from eating the height ---- */
@media (max-height: 640px) and (min-width: 761px) {
    .vsx-hero {
        min-height: 110px;
    }

    .vsx-hero img {
        max-height: 140px;
    }
}
</style>
