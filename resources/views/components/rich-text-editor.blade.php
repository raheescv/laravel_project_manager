@props([
    /** Optional field label rendered above the editor. */
    'label' => null,
    /** Small helper line rendered under the editor. */
    'help' => null,
    'placeholder' => 'Start typing…',
    /** Editing area height in px (it can still be dragged taller). */
    'height' => 220,
    /** Placeholder chips: ['{token}' => 'What it means'] — clicking one inserts it at the caret. */
    'tokens' => [],
    /** Show the "HTML" source toggle. */
    'source' => true,
    /** Start the editing area right-to-left (Arabic-first fields). */
    'rtl' => false,
    'disabled' => false,
])

@php
    // The value is driven entirely through wire:model — the wrapper itself must not
    // carry it, or Livewire would try to bind a <div>.
    $rteModel = $attributes->wire('model')->value();
    $rteId = $attributes->get('id') ?: 'rte-'.\Illuminate\Support\Str::random(10);
@endphp

<div {{ $attributes->whereDoesntStartWith('wire:')->except(['id', 'class'])->merge(['class' => 'rte '.$attributes->get('class', '')]) }}
    id="{{ $rteId }}"
    wire:ignore
    x-data="richTextEditor({
        model: @js($rteModel),
        rtl: @js((bool) $rtl),
        disabled: @js((bool) $disabled),
    })">

    @if ($label)
        <label class="form-label fw-medium small mb-1">{{ $label }}</label>
    @endif

    <div class="rte-shell" :class="{ 'is-focused': focused, 'is-source': source }">
        <div class="rte-bar">
            <div class="rte-grp">
                <button type="button" class="rte-btn" title="Bold (Ctrl+B)" x-on:click="cmd('bold')"><i class="fa fa-bold"></i></button>
                <button type="button" class="rte-btn" title="Italic (Ctrl+I)" x-on:click="cmd('italic')"><i class="fa fa-italic"></i></button>
                <button type="button" class="rte-btn" title="Underline (Ctrl+U)" x-on:click="cmd('underline')"><i class="fa fa-underline"></i></button>
                <button type="button" class="rte-btn" title="Strikethrough" x-on:click="cmd('strikeThrough')"><i class="fa fa-strikethrough"></i></button>
            </div>

            <div class="rte-grp">
                <select class="rte-select" title="Text style" x-on:change="block($event.target.value); $event.target.value = ''">
                    <option value="" selected>Style</option>
                    <option value="p">Paragraph</option>
                    <option value="h2">Heading</option>
                    <option value="h3">Sub-heading</option>
                    <option value="h4">Small heading</option>
                    <option value="blockquote">Indented block</option>
                </select>
            </div>

            <div class="rte-grp">
                <button type="button" class="rte-btn" title="Bulleted list" x-on:click="cmd('insertUnorderedList')"><i class="fa fa-list-ul"></i></button>
                <button type="button" class="rte-btn" title="Numbered list" x-on:click="cmd('insertOrderedList')"><i class="fa fa-list-ol"></i></button>
                <button type="button" class="rte-btn" title="Decrease indent" x-on:click="cmd('outdent')"><i class="fa fa-outdent"></i></button>
                <button type="button" class="rte-btn" title="Increase indent" x-on:click="cmd('indent')"><i class="fa fa-indent"></i></button>
            </div>

            <div class="rte-grp">
                <button type="button" class="rte-btn" title="Align left" x-on:click="cmd('justifyLeft')"><i class="fa fa-align-left"></i></button>
                <button type="button" class="rte-btn" title="Align centre" x-on:click="cmd('justifyCenter')"><i class="fa fa-align-center"></i></button>
                <button type="button" class="rte-btn" title="Align right" x-on:click="cmd('justifyRight')"><i class="fa fa-align-right"></i></button>
                <button type="button" class="rte-btn" title="Justify" x-on:click="cmd('justifyFull')"><i class="fa fa-align-justify"></i></button>
            </div>

            <div class="rte-grp">
                <button type="button" class="rte-btn rte-btn-txt" title="Left-to-right paragraph" x-on:click="direction('ltr')">LTR</button>
                <button type="button" class="rte-btn rte-btn-txt" title="Right-to-left paragraph (Arabic)" x-on:click="direction('rtl')">RTL</button>
            </div>

            <div class="rte-grp">
                <button type="button" class="rte-btn" title="Insert link" x-on:click="link()"><i class="fa fa-link"></i></button>
                <button type="button" class="rte-btn" title="Remove link" x-on:click="cmd('unlink')"><i class="fa fa-chain-broken"></i></button>
                <button type="button" class="rte-btn" title="Clear formatting" x-on:click="clearFormat()"><i class="fa fa-eraser"></i></button>
            </div>

            <div class="rte-grp">
                <button type="button" class="rte-btn" title="Undo" x-on:click="cmd('undo')"><i class="fa fa-undo"></i></button>
                <button type="button" class="rte-btn" title="Redo" x-on:click="cmd('redo')"><i class="fa fa-repeat"></i></button>
            </div>

            @if ($source)
                <div class="rte-grp">
                    <button type="button" class="rte-btn rte-btn-txt" :class="{ 'is-on': source }"
                        title="Edit the underlying HTML" x-on:click="toggleSource()">
                        <i class="fa fa-code me-1"></i><span x-text="source ? 'Visual' : 'HTML'"></span>
                    </button>
                </div>
            @endif
        </div>

        @if (! empty($tokens))
            <div class="rte-tokens">
                <span class="rte-tokens-t">Insert</span>
                @foreach ($tokens as $token => $meaning)
                    <button type="button" class="rte-token" title="{{ $meaning }}"
                        x-on:click="insert(@js($token))">{{ $token }}</button>
                @endforeach
            </div>
        @endif

        <div class="rte-canvas" x-ref="canvas" x-show="! source"
            contenteditable="{{ $disabled ? 'false' : 'true' }}"
            @if ($rtl) dir="rtl" @endif
            style="min-height: {{ (int) $height }}px"
            data-placeholder="{{ $placeholder }}"
            x-on:input="fromCanvas()"
            x-on:blur="focused = false; fromCanvas()"
            x-on:focus="focused = true"
            x-on:paste="onPaste($event)"
            x-on:keydown="onKeydown($event)"></div>

        <textarea class="rte-source" x-ref="source" x-show="source" x-cloak spellcheck="false"
            style="min-height: {{ (int) $height }}px"
            x-on:input="fromSource()" @disabled($disabled)></textarea>
    </div>

    @if ($help)
        <div class="form-text mt-1">{{ $help }}</div>
    @endif
</div>

@once
    @push('styles')
        <style>
            /* ── Reusable rich-text editor (x-rich-text-editor) ─────────────── */
            .rte-shell { border: 1px solid var(--bs-border-color); border-radius: .5rem; background: var(--bs-body-bg); overflow: hidden; }
            .rte-shell.is-focused { border-color: var(--bs-primary); box-shadow: 0 0 0 .18rem rgba(var(--bs-primary-rgb), .15); }
            .rte-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .15rem .35rem; padding: .3rem .4rem;
                       border-bottom: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); }
            .rte-grp { display: flex; align-items: center; gap: .1rem; padding-right: .35rem; margin-right: .1rem;
                       border-right: 1px solid var(--bs-border-color); }
            .rte-grp:last-child { border-right: 0; padding-right: 0; }
            .rte-btn { border: 0; background: transparent; color: var(--bs-body-color); border-radius: .3rem;
                       width: 26px; height: 26px; line-height: 1; font-size: .78rem; display: inline-flex;
                       align-items: center; justify-content: center; transition: background-color .12s ease, color .12s ease; }
            .rte-btn:hover { background: rgba(var(--bs-primary-rgb), .12); color: var(--bs-primary); }
            .rte-btn.is-on { background: rgba(var(--bs-primary-rgb), .16); color: var(--bs-primary); }
            .rte-btn-txt { width: auto; padding: 0 .45rem; font-size: .68rem; font-weight: 600; letter-spacing: .02em; }
            .rte-select { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color);
                          border-radius: .3rem; font-size: .72rem; height: 26px; padding: 0 .3rem; max-width: 120px; }
            .rte-tokens { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem; padding: .3rem .45rem;
                          border-bottom: 1px solid var(--bs-border-color); background: var(--bs-body-bg); }
            .rte-tokens-t { font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
                            color: var(--bs-secondary-color); margin-right: .15rem; }
            .rte-token { border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); color: var(--bs-body-color);
                         border-radius: 999px; font-size: .68rem; font-family: var(--bs-font-monospace); padding: .05rem .45rem; }
            .rte-token:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
            .rte-canvas { padding: .6rem .75rem; font-size: .84rem; line-height: 1.55; color: var(--bs-body-color);
                          outline: 0; overflow-y: auto; resize: vertical; max-height: 60vh; }
            .rte-canvas:empty::before { content: attr(data-placeholder); color: var(--bs-secondary-color); }
            .rte-canvas > *:first-child { margin-top: 0; }
            .rte-canvas > *:last-child { margin-bottom: 0; }
            .rte-canvas p { margin: 0 0 .5rem; }
            .rte-canvas h2 { font-size: 1rem; font-weight: 700; margin: .6rem 0 .35rem; }
            .rte-canvas h3 { font-size: .92rem; font-weight: 700; margin: .55rem 0 .3rem; }
            .rte-canvas h4 { font-size: .86rem; font-weight: 700; margin: .5rem 0 .3rem; }
            .rte-canvas ul, .rte-canvas ol { margin: 0 0 .5rem; padding-inline-start: 1.25rem; }
            .rte-canvas li { margin-bottom: .15rem; }
            .rte-canvas blockquote { margin: 0 0 .5rem; padding-inline-start: 1.15rem; }
            .rte-canvas [dir="rtl"] { text-align: right; }
            .rte-source { width: 100%; border: 0; outline: 0; resize: vertical; padding: .6rem .75rem; max-height: 60vh;
                          font-family: var(--bs-font-monospace); font-size: .74rem; line-height: 1.6;
                          background: var(--bs-body-bg); color: var(--bs-body-color); }
        </style>
    @endpush

    @push('scripts')
        <script>
            /**
             * Alpine factory behind the rich-text-editor component. Content lives in a
             * contenteditable canvas (or a raw-HTML textarea) and is pushed into the
             * Livewire property named by `model` — deferred, so typing never fires a
             * round trip. The server sanitises whatever arrives (App\Support\RichText).
             */
            window.richTextEditor = function (config) {
                return {
                    model: config.model || null,
                    rtl: !!config.rtl,
                    disabled: !!config.disabled,
                    source: false,
                    focused: false,
                    html: '',

                    init() {
                        this.html = this.pull();
                        this.render();

                        // Lets a parent (a Reset button, a tab switch, a fresh record)
                        // ask every editor — or one model — to re-read its value.
                        window.addEventListener('rich-text:refresh', (event) => {
                            const only = event.detail && event.detail.model;
                            if (only && only !== this.model) return;
                            this.html = this.pull();
                            this.render();
                            this.announce();
                        });
                    },

                    // ── Value plumbing ──────────────────────────────────────
                    pull() {
                        if (!this.model || !this.$wire) return '';
                        const value = this.$wire.get(this.model);
                        return value === null || value === undefined ? '' : String(value);
                    },
                    render() {
                        if (this.$refs.canvas) this.$refs.canvas.innerHTML = this.html;
                        if (this.$refs.source) this.$refs.source.value = this.html;
                    },
                    commit(value) {
                        this.html = value;
                        // `false` = deferred: state is kept client-side until the next request.
                        if (this.model && this.$wire) this.$wire.set(this.model, value, false);
                        this.announce();
                    },
                    announce() {
                        this.$dispatch('rich-text-input', { model: this.model, value: this.html });
                    },
                    fromCanvas() {
                        const value = this.$refs.canvas.innerHTML;
                        this.commit(this.isEmpty(value) ? '' : value);
                    },
                    fromSource() {
                        const value = this.$refs.source.value;
                        this.html = value;
                        if (this.model && this.$wire) this.$wire.set(this.model, value, false);
                        this.announce();
                    },
                    isEmpty(value) {
                        return !String(value).replace(/<br\s*\/?>|&nbsp;|<p>\s*<\/p>|\s/gi, '').replace(/<[^>]*>/g, '').length
                            && !/<(img|hr|table)/i.test(value);
                    },

                    // ── Commands ────────────────────────────────────────────
                    focus() {
                        if (this.source || this.disabled) return false;
                        const canvas = this.$refs.canvas;
                        if (!canvas.contains(document.getSelection()?.anchorNode)) canvas.focus();
                        return true;
                    },
                    cmd(name, value = null) {
                        if (!this.focus()) return;
                        // Semantic tags (<strong>, <em>) instead of inline styles — they survive
                        // sanitising. Indent is the exception: with semantic markup Chrome wraps
                        // the line in a <blockquote>, which prints as a quote bar.
                        const asCss = name === 'indent' || name === 'outdent';
                        try { document.execCommand('styleWithCSS', false, asCss); } catch (e) {}
                        document.execCommand(name, false, value);
                        this.fromCanvas();
                    },
                    block(tag) {
                        if (!tag) return;
                        this.cmd('formatBlock', '<' + tag + '>');
                    },
                    clearFormat() {
                        this.cmd('removeFormat');
                        this.cmd('formatBlock', '<p>');
                    },
                    link() {
                        const url = window.prompt('Link address', 'https://');
                        if (url) this.cmd('createLink', url);
                    },
                    insert(text) {
                        if (this.source) {
                            const area = this.$refs.source, at = area.selectionStart;
                            area.value = area.value.slice(0, at) + text + area.value.slice(area.selectionEnd);
                            area.setSelectionRange(at + text.length, at + text.length);
                            this.fromSource();
                            return;
                        }
                        this.cmd('insertText', text);
                    },
                    /** Set the paragraph direction — RTL blocks keep Arabic clauses readable. */
                    direction(dir) {
                        if (!this.focus()) return;
                        const canvas = this.$refs.canvas;
                        let node = document.getSelection()?.anchorNode;
                        if (!node) return;
                        if (node.nodeType === 3) node = node.parentNode;
                        while (node && node !== canvas && node.parentNode !== canvas) node = node.parentNode;
                        if (!node || node === canvas) {
                            // Nothing block-level yet — wrap the caret's line first.
                            document.execCommand('formatBlock', false, '<p>');
                            node = document.getSelection()?.anchorNode;
                            if (node && node.nodeType === 3) node = node.parentNode;
                            while (node && node !== canvas && node.parentNode !== canvas) node = node.parentNode;
                        }
                        if (!node || node === canvas) return;
                        node.setAttribute('dir', dir);
                        node.style.textAlign = dir === 'rtl' ? 'right' : 'left';
                        this.fromCanvas();
                    },
                    toggleSource() {
                        if (this.source) {
                            this.html = this.$refs.source.value;
                            this.source = false;
                            this.$nextTick(() => { this.$refs.canvas.innerHTML = this.html; });
                        } else {
                            this.html = this.$refs.canvas.innerHTML;
                            this.source = true;
                            this.$nextTick(() => { this.$refs.source.value = this.html; });
                        }
                    },

                    // ── Input handling ──────────────────────────────────────
                    onKeydown(event) {
                        if (!(event.ctrlKey || event.metaKey)) return;
                        const key = event.key.toLowerCase();
                        if (['b', 'i', 'u'].includes(key)) {
                            // Let the browser apply it, then capture the result.
                            this.$nextTick(() => this.fromCanvas());
                        }
                    },
                    /** Paste keeps structure (headings, lists) but drops Word's styling noise. */
                    onPaste(event) {
                        const clipboard = event.clipboardData;
                        if (!clipboard) return;
                        const html = clipboard.getData('text/html');
                        event.preventDefault();
                        if (html) {
                            document.execCommand('insertHTML', false, this.scrub(html));
                        } else {
                            document.execCommand('insertText', false, clipboard.getData('text/plain'));
                        }
                        this.fromCanvas();
                    },
                    scrub(html) {
                        const holder = document.createElement('div');
                        holder.innerHTML = html
                            .replace(/<!--[\s\S]*?-->/g, '')
                            .replace(/<(script|style|meta|link)[\s\S]*?<\/\1>/gi, '');
                        holder.querySelectorAll('*').forEach((node) => {
                            const dir = node.getAttribute('dir');
                            [...node.attributes].forEach((attr) => node.removeAttribute(attr.name));
                            if (dir === 'rtl' || dir === 'ltr') node.setAttribute('dir', dir);
                        });
                        return holder.innerHTML;
                    },
                };
            };
        </script>
    @endpush
@endonce
