{{-- Checklist tab "Walkthrough Split" (.clx). Every colour comes from the RentOut view's
     .rvx surface tokens or the Bootstrap theme, so it follows the settings colour and dark mode. Touch targets are 44px; the
     layout answers to the tab's own width (container queries), not the window's. --}}
<style>
    /* The tabs card clips its rounded corners with overflow:hidden, which would also stop
       the room list and the save dock sticking. clip keeps the corners without that. */
    .rvx-mgmt-card:has(.clx) {
        overflow: clip !important;
    }

    .clx {
        --clx-brand: var(--bs-primary);
        --clx-brand-soft: color-mix(in srgb, var(--clx-brand) 10%, transparent);
        --clx-brand-line: color-mix(in srgb, var(--clx-brand) 30%, var(--bs-border-color));
        --clx-ok: var(--bs-success);
        --clx-no: var(--bs-danger);
        --clx-line: var(--border, var(--bs-border-color));
        --clx-muted: var(--bs-secondary-color);
        --clx-soft: var(--surface-2, var(--bs-tertiary-bg));
        --clx-card: var(--surface, var(--bs-body-bg));
        --clx-r: 14px;
        --clx-tap: 44px;
        container-type: inline-size;
        -webkit-tap-highlight-color: transparent;
    }

    .clx [x-cloak] {
        display: none !important;
    }

    .clx[data-phase="move_in"] [data-only="move_out"],
    .clx[data-phase="move_out"] [data-only="move_in"] {
        display: none !important;
    }

    .clx .clx-brand {
        color: var(--clx-brand);
    }

    .clx .clx-grow {
        flex: 1;
    }

    .clx .clx-lbl {
        font-size: 10.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--clx-muted);
    }

    /* ---- controls ---- */
    .clx .clx-btn {
        min-height: 40px;
        padding: 0 .9rem;
        border-radius: 10px;
        border: 1px solid var(--clx-line);
        background: var(--clx-card);
        color: var(--bs-body-color);
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .8rem;
        font-weight: 500;
        white-space: nowrap;
        text-decoration: none;
        cursor: pointer;
        transition: border-color .15s, color .15s, filter .15s;
    }

    .clx .clx-btn:hover {
        border-color: var(--clx-brand);
        color: var(--clx-brand);
    }

    .clx .clx-btn.is-primary,
    .clx .clx-btn.is-success {
        background: var(--clx-brand);
        border-color: var(--clx-brand);
        color: #fff;
    }

    .clx .clx-btn.is-success {
        background: var(--clx-ok);
        border-color: var(--clx-ok);
    }

    .clx .clx-btn.is-primary:hover,
    .clx .clx-btn.is-success:hover {
        filter: brightness(1.08);
        color: #fff;
    }

    .clx .clx-btn.is-lg {
        min-height: var(--clx-tap);
        padding: 0 1.3rem;
        font-weight: 600;
    }

    .clx .clx-btn:disabled {
        opacity: .6;
    }

    .clx .clx-icon-btn {
        width: 38px;
        height: 38px;
        flex: none;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--clx-muted);
        cursor: pointer;
    }

    .clx .clx-icon-btn:hover {
        background: color-mix(in srgb, var(--clx-no) 12%, transparent);
        color: var(--clx-no);
    }

    .clx .clx-in {
        width: 100%;
        min-height: var(--clx-tap);
        padding: 0 .75rem;
        border: 1px solid var(--clx-line);
        border-radius: 10px;
        background: var(--clx-card);
        color: var(--bs-body-color);
        font-size: .85rem;
    }

    .clx textarea.clx-in {
        padding: .6rem .75rem;
        min-height: 64px;
        resize: vertical;
    }

    .clx .clx-in:focus,
    .clx .clx-qty input:focus,
    .clx .clx-damage input:focus {
        outline: 0;
        border-color: var(--clx-brand);
        box-shadow: 0 0 0 3px var(--clx-brand-soft);
    }

    .clx .clx-field {
        display: flex;
        flex-direction: column;
        gap: .3rem;
        margin: 0;
    }

    .clx .clx-seg {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 2px;
        padding: 4px;
        border-radius: 12px;
        background: var(--clx-soft);
        border: 1px solid var(--clx-line);
    }

    .clx .clx-seg button {
        min-height: 38px;
        padding: 0 1rem;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: var(--clx-muted);
        font-size: .8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        cursor: pointer;
    }

    .clx .clx-seg button.on {
        background: var(--clx-card);
        color: var(--clx-brand);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .08), 0 0 0 1px var(--clx-brand-line);
    }

    .clx .clx-seg-n {
        font-size: .7rem;
        padding: 0 .45rem;
        border-radius: 20px;
        background: var(--clx-brand-soft);
        color: var(--clx-brand);
    }

    .clx .clx-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .15rem .6rem;
        border-radius: 20px;
        font-size: .7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .clx .is-success {
        --tone: var(--bs-success);
    }

    .clx .is-warning {
        --tone: var(--bs-warning);
    }

    .clx .is-primary {
        --tone: var(--clx-brand);
    }

    .clx .is-danger {
        --tone: var(--bs-danger);
    }

    .clx .clx-pill[class*="is-"]:not(.is-muted),
    .clx .clx-chip[class*="is-"] {
        background: color-mix(in srgb, var(--tone) 14%, transparent);
        color: color-mix(in srgb, var(--tone) 80%, var(--bs-body-color));
        border-color: transparent;
    }

    .clx .clx-pill.is-muted {
        background: var(--clx-soft);
        color: var(--clx-muted);
        border: 1px solid var(--clx-line);
    }

    /* ---- hero + phase cards + people ---- */
    .clx .clx-hero {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem 1.1rem;
        margin-bottom: .9rem;
        border-radius: var(--clx-r);
        border: 1px solid var(--clx-brand-line);
        background: linear-gradient(135deg, color-mix(in srgb, var(--clx-brand) 9%, var(--clx-card)), var(--clx-card) 70%);
    }

    .clx .clx-hero h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .clx .clx-meta {
        margin-top: .15rem;
        font-size: .78rem;
        color: var(--clx-muted);
    }

    .clx .clx-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        justify-content: flex-end;
    }

    .clx .clx-phases {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: .7rem;
        margin-bottom: .9rem;
    }

    .clx .clx-phase {
        padding: .85rem .95rem;
        border-radius: var(--clx-r);
        border: 1px solid var(--clx-line);
        background: var(--clx-card);
    }

    .clx .clx-phase-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .55rem;
    }

    .clx .clx-phase-title {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-weight: 600;
        font-size: .85rem;
    }

    .clx .clx-phase-count {
        display: flex;
        justify-content: space-between;
        font-size: .74rem;
        color: var(--clx-muted);
    }

    .clx .clx-phase-count b {
        color: var(--bs-body-color);
    }

    .clx .clx-meter {
        height: 6px;
        margin: .2rem 0 .65rem;
        border-radius: 6px;
        overflow: hidden;
        background: var(--clx-soft);
    }

    .clx .clx-meter i {
        display: block;
        height: 100%;
        border-radius: 6px;
        background: var(--clx-ok);
        transition: width .3s;
    }

    .clx .clx-sigs {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .clx .clx-sig {
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .65rem .3rem .3rem;
        border-radius: 30px;
        border: 1px solid var(--clx-line);
        font-size: .74rem;
    }

    .clx .clx-sig-av {
        width: 26px;
        height: 26px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        font-size: .7rem;
        background: var(--clx-soft);
        color: var(--clx-muted);
    }

    .clx .clx-sig.on {
        border-color: color-mix(in srgb, var(--clx-ok) 35%, var(--clx-line));
    }

    .clx .clx-sig.on .clx-sig-av {
        background: var(--clx-ok);
        color: #fff;
    }

    .clx .clx-people {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .6rem;
        margin-bottom: 1rem;
    }

    .clx .clx-people .ts-control {
        min-height: var(--clx-tap);
        border-radius: 10px;
        display: flex;
        align-items: center;
    }

    .clx .clx-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
        padding: 2.5rem 1rem;
        margin-bottom: 1rem;
        border: 1.5px dashed var(--clx-line);
        border-radius: var(--clx-r);
        color: var(--clx-muted);
        text-align: center;
    }

    .clx .clx-empty > i {
        font-size: 2rem;
        opacity: .35;
    }

    .clx .clx-empty b {
        color: var(--bs-body-color);
    }

    /* ---- split: rooms + pane ---- */
    .clx .clx-split {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .clx .clx-nav {
        position: sticky;
        top: 5rem;
        overflow: hidden;
        border: 1px solid var(--clx-line);
        border-radius: var(--clx-r);
        background: var(--clx-card);
    }

    .clx .clx-nav-item {
        width: 100%;
        min-height: 58px;
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .75rem;
        border: 0;
        border-bottom: 1px solid var(--clx-line);
        background: transparent;
        color: var(--bs-body-color);
        text-align: start;
        font-size: .83rem;
        font-weight: 600;
        cursor: pointer;
    }

    .clx .clx-nav-item:hover {
        background: var(--clx-soft);
    }

    .clx .clx-nav-item.on {
        background: var(--clx-brand-soft);
        color: var(--clx-brand);
        box-shadow: inset 3px 0 0 var(--clx-brand);
    }

    .clx .clx-nav-text {
        flex: 1;
        min-width: 0;
        line-height: 1.3;
    }

    .clx .clx-nav-text small {
        display: block;
        font-weight: 400;
        font-size: .7rem;
        color: var(--clx-muted);
    }

    .clx .clx-ring {
        --v: 0;
        width: 36px;
        height: 36px;
        flex: none;
        display: grid;
        place-items: center;
        border-radius: 50%;
        font-size: .68rem;
        font-weight: 700;
        color: var(--bs-body-color);
        background:
            radial-gradient(closest-side, var(--clx-card) 76%, transparent 78%),
            conic-gradient(var(--clx-ok) calc(var(--v) * 1%), var(--clx-line) 0);
    }

    .clx .clx-nav-add {
        padding: .55rem .75rem;
    }

    .clx .clx-room-select,
    .clx .clx-pane-add {
        display: none;
    }

    .clx .clx-pane-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .6rem;
        margin-bottom: .75rem;
    }

    .clx .clx-pane-head h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .clx .clx-pager {
        display: flex;
        gap: .5rem;
        margin-top: .9rem;
    }

    /* ---- item ---- */
    .clx .clx-item {
        display: grid;
        grid-template-columns: 92px minmax(0, 1fr);
        gap: .9rem;
        padding: .8rem;
        margin-bottom: .6rem;
        border: 1px solid var(--clx-line);
        border-radius: var(--clx-r);
        background: var(--clx-card);
        transition: border-color .15s;
    }

    .clx[data-phase="move_out"] .clx-item.is-damaged {
        border-color: color-mix(in srgb, var(--clx-no) 45%, var(--clx-line));
        background: linear-gradient(color-mix(in srgb, var(--clx-no) 6%, var(--clx-card)), var(--clx-card) 60%);
    }

    .clx .clx-item-top {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .clx .clx-item-name {
        flex: 1;
        min-width: 0;
        font-weight: 600;
        font-size: .9rem;
        line-height: 1.3;
    }

    .clx .clx-item-ctl {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .6rem;
        margin-top: .6rem;
    }

    .clx .clx-item-ctl > .clx-in {
        flex: 1;
        width: auto;
        min-width: 160px;
    }

    .clx .clx-qty {
        display: inline-flex;
        align-items: center;
        height: 38px;
        flex: none;
        overflow: hidden;
        border: 1px solid var(--clx-line);
        border-radius: 10px;
    }

    .clx .clx-qty button {
        width: 34px;
        height: 100%;
        border: 0;
        background: var(--clx-soft);
        color: var(--clx-muted);
        cursor: pointer;
    }

    .clx .clx-qty input,
    .clx .clx-damage input {
        border: 0;
        background: transparent;
        color: var(--bs-body-color);
        -moz-appearance: textfield;
        appearance: textfield;
    }

    .clx .clx-qty input {
        width: 38px;
        height: 100%;
        text-align: center;
        font-weight: 600;
        font-size: .85rem;
    }

    .clx .clx-qty input::-webkit-inner-spin-button,
    .clx .clx-damage input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .clx .clx-tick {
        min-width: 112px;
        height: var(--clx-tap);
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        border-radius: 12px;
        border: 1.5px solid var(--clx-line);
        background: var(--clx-card);
        color: var(--clx-muted);
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, border-color .15s, color .15s;
    }

    .clx .clx-tick.on {
        background: var(--clx-ok);
        border-color: var(--clx-ok);
        color: #fff;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--clx-ok) 30%, transparent);
    }

    .clx .clx-tri {
        display: inline-flex;
        flex: none;
        overflow: hidden;
        border: 1px solid var(--clx-line);
        border-radius: 12px;
    }

    .clx .clx-tri button {
        height: var(--clx-tap);
        padding: 0 .85rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 0;
        background: var(--clx-card);
        color: var(--clx-muted);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
    }

    .clx .clx-tri button + button {
        border-inline-start: 1px solid var(--clx-line);
    }

    .clx .clx-tri .is-good.on {
        background: var(--clx-ok);
        color: #fff;
    }

    .clx .clx-tri .is-bad.on {
        background: var(--clx-no);
        color: #fff;
    }

    .clx .clx-damage {
        width: 140px;
        height: var(--clx-tap);
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin: 0;
        padding: 0 .7rem;
        border: 1px solid var(--clx-line);
        border-radius: 10px;
        font-size: .72rem;
        color: var(--clx-muted);
    }

    .clx .clx-damage input {
        width: 100%;
        min-width: 0;
        text-align: end;
        font-size: .85rem;
        font-weight: 600;
    }

    .clx .clx-damage:focus-within {
        border-color: var(--clx-brand);
        box-shadow: 0 0 0 3px var(--clx-brand-soft);
    }

    /* ---- photo tile (x-rent-out.checklist.photo) ---- */
    .clx .clx-photo-wrap {
        position: relative;
        width: 92px;
    }

    .clx .clx-photo {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        margin: 0;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 12px;
        border: 1.5px dashed var(--clx-line);
        background: var(--clx-soft);
        color: var(--clx-muted);
        cursor: pointer;
    }

    .clx .clx-photo.has {
        border-style: solid;
    }

    .clx .clx-photo.ro:not(.has) {
        cursor: default;
        opacity: .7;
    }

    .clx .clx-photo:not(.has):not(.ro):hover {
        border-color: var(--clx-brand);
        color: var(--clx-brand);
    }

    .clx .clx-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: zoom-in;
    }

    .clx .clx-photo-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        font-size: 10.5px;
        font-weight: 600;
    }

    .clx .clx-photo-empty i {
        font-size: 20px;
    }

    .clx .clx-photo-tag {
        position: absolute;
        top: 5px;
        inset-inline-start: 5px;
        padding: 0 5px;
        border-radius: 5px;
        background: rgba(0, 0, 0, .55);
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .clx .clx-photo-act,
    .clx .clx-photo-del {
        position: absolute;
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border: 0;
        border-radius: 9px;
        background: rgba(0, 0, 0, .55);
        color: #fff;
        font-size: 12px;
        cursor: pointer;
    }

    .clx .clx-photo-act {
        inset: auto 5px 5px auto;
    }

    .clx .clx-photo-del {
        top: 5px;
        inset-inline-end: 5px;
    }

    .clx .clx-photo-del:hover {
        background: var(--clx-no);
    }

    .clx .clx-photo-prog {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--clx-card) 72%, transparent);
        color: var(--clx-brand);
        font-size: .75rem;
        font-weight: 700;
    }

    .clx .clx-photo-prog::after {
        content: "";
        position: absolute;
        left: 7px;
        right: 7px;
        bottom: 7px;
        height: 4px;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--clx-brand) var(--p, 0%), var(--clx-line) var(--p, 0%));
    }

    /* ---- fixture comments ---- */
    .clx .clx-fx {
        margin-top: .8rem;
        overflow: hidden;
        border: 1px solid var(--clx-line);
        border-radius: var(--clx-r);
        background: var(--clx-card);
    }

    .clx .clx-fx-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        padding: .6rem .85rem;
        border-bottom: 1px solid var(--clx-line);
        background: var(--clx-soft);
        font-size: .82rem;
        font-weight: 600;
    }

    .clx .clx-ar {
        font-weight: 400;
        color: var(--clx-muted);
        margin-inline-start: .3rem;
    }

    .clx .clx-fx-entry {
        display: grid;
        grid-template-columns: 92px 92px minmax(0, 1fr) 210px;
        gap: .75rem;
        align-items: start;
        padding: .8rem .85rem;
        border-bottom: 1px solid var(--clx-line);
    }

    .clx .clx-fx-comment {
        height: 100%;
        min-height: 92px;
    }

    .clx .clx-fx-status {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    .clx .clx-fx-status .clx-in {
        min-height: 38px;
        font-size: .8rem;
    }

    .clx .clx-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
    }

    .clx .clx-chip {
        min-height: 34px;
        padding: 0 .75rem;
        border-radius: 20px;
        border: 1px solid var(--clx-line);
        background: var(--clx-card);
        color: var(--clx-muted);
        font-size: .72rem;
        font-weight: 600;
        cursor: pointer;
    }

    .clx .clx-fx-empty {
        padding: .8rem .85rem;
        font-size: .8rem;
        font-style: italic;
        color: var(--clx-muted);
        border-bottom: 1px solid var(--clx-line);
    }

    .clx .clx-fx-foot {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        padding: .55rem .85rem;
        font-size: .76rem;
        color: var(--clx-muted);
    }

    /* ---- add area ---- */
    .clx .clx-add-area-toggle {
        width: 100%;
        min-height: 40px;
        border: 1px dashed var(--clx-line);
        border-radius: 10px;
        background: transparent;
        color: var(--clx-muted);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
    }

    .clx .clx-add-area-toggle:hover {
        border-color: var(--clx-brand);
        color: var(--clx-brand);
    }

    .clx .clx-add-area-form {
        display: flex;
        flex-direction: column;
        gap: .45rem;
    }

    /* ---- save dock ---- */
    .clx .clx-dock {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem 1rem;
        margin-top: 1rem;
        padding: .65rem .85rem;
        border: 1px solid var(--clx-line);
        border-radius: var(--clx-r);
        box-shadow: 0 -6px 18px rgba(0, 0, 0, .06);
        background: color-mix(in srgb, var(--clx-card) 88%, transparent);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        font-size: .8rem;
        color: var(--clx-muted);
    }

    .clx .clx-dock b {
        color: var(--bs-body-color);
    }

    .clx .clx-dock-hint {
        font-size: .74rem;
    }

    /* ---- iPad (and narrow desktop) ---- */
    @container (max-width: 900px) {
        .clx .clx-people {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .clx .clx-split {
            grid-template-columns: 196px minmax(0, 1fr);
        }

        .clx .clx-fx-entry {
            grid-template-columns: 92px 92px minmax(0, 1fr);
        }

        .clx .clx-fx-status {
            grid-column: 1 / -1;
        }

        .clx .clx-dock-hint {
            display: none;
        }
    }

    /* ---- phone / iPad split-view ---- */
    @container (max-width: 620px) {
        .clx .clx-hero {
            grid-template-columns: 1fr;
        }

        .clx .clx-actions {
            justify-content: flex-start;
        }

        .clx .clx-people,
        .clx .clx-split {
            grid-template-columns: minmax(0, 1fr);
        }

        .clx .clx-nav {
            display: none;
        }

        .clx .clx-room-select,
        .clx .clx-pane-add {
            display: block;
            margin-bottom: .75rem;
        }

        .clx .clx-item {
            grid-template-columns: 76px minmax(0, 1fr);
            gap: .7rem;
        }

        .clx .clx-item-photo .clx-photo-wrap {
            width: 76px;
        }

        .clx .clx-item-ctl > .clx-in {
            flex-basis: 100%;
        }

        .clx .clx-item-top {
            flex-wrap: wrap;
        }

        .clx .clx-item-name {
            flex-basis: 100%;
        }

        .clx .clx-fx-entry {
            grid-template-columns: 1fr 1fr;
        }

        .clx .clx-fx-entry .clx-photo-wrap {
            width: 100%;
        }

        .clx .clx-fx-comment {
            grid-column: 1 / -1;
        }
    }
</style>
