{{-- The Unit Handover & Snagging document's own styles, shared by the PDF
     (print.rentout.checklist) and the on-screen sign page (rentout-checklist.print)
     so both render the one layout. Scoped under .ck-doc so the sign page's
     Bootstrap chrome is left alone. --}}
<style>
        .ck-doc, .ck-doc * { box-sizing: border-box; }
        /* Rendered by Chrome (Browsershot) for the PDF and by the browser on the sign
           page — the Arabic faces are what shape and order RTL clauses in the
           declaration correctly. */
        .ck-doc { font-family: 'DejaVu Sans', 'Noto Naskh Arabic', 'Noto Sans Arabic', 'Geeza Pro', Arial, sans-serif;
                  font-size: 10px; color: #2b2b2b; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        /* The PDF shell stretches .wrap to whole pages so this block lands at the foot
           of the last one; on screen it simply follows the inventory. */
        .ck-doc .wrap { display: flex; flex-direction: column; }
        .accept-group { margin-top: auto; }
        .title-band { background: #7a6a2f; color: #fff; border-radius: 4px; width: 100%; }
        .title-band td { vertical-align: middle; padding: 8px 10px; }
        .title-band .tb-logo { width: 110px; }
        .title-band .tb-title { text-align: center; }
        .title-band .t { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
        .title-band .s { font-size: 9px; opacity: .9; }
        .logo-box { background: #fff; border-radius: 4px; padding: 4px; text-align: center; }
        .logo-box img { max-width: 96px; max-height: 46px; }
        .sec { background: #7a6a2f; color: #fff; font-size: 10px; font-weight: bold; letter-spacing: .4px;
               padding: 4px 8px; margin: 12px 0 6px; text-transform: uppercase; border-radius: 3px; }
        .ck-doc table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 7px; border: 1px solid #d8d4c4; font-size: 10px; }
        .meta .lbl { color: #6b6550; width: 17%; background: #f6f3e9; }
        .meta .val { width: 33%; font-weight: bold; }
        /* Fixed layout so the measured column widths are honoured instead of being
           re-guessed from content; break-word keeps long comments inside their cell. */
        .items { table-layout: fixed; }
        .items th { background: #efe9d6; color: #4a432b; border: 1px solid #ccc6b0; padding: 4px 6px; font-size: 9.5px; }
        .items td { border: 1px solid #ddd; padding: 3px 6px; font-size: 9.5px;
                    word-wrap: break-word; overflow-wrap: break-word; }
        .items tr.cat td { background: #f3efe2; color: #6a5f33; font-weight: bold; text-transform: uppercase; font-size: 9px; letter-spacing: .3px; }
        .items tr:nth-child(even) td { background: #fcfbf7; }
        /* Fixture Comments — the rectification record for one area. Printed as a table of
           its own inside a full-width cell rather than threaded through the inventory
           columns above: before/after photos and an owner signature need room the item
           grid can't spare, and its column count doesn't match. */
        .items tr.fx > td { padding: 0; border-top: 2px solid #d8d0b4; }
        .fx-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .fx-table td { border: 1px solid #e4e0d2; padding: 3px 6px; font-size: 9px; vertical-align: middle;
                       background: #fdfcf8; word-wrap: break-word; overflow-wrap: break-word; }
        .fx-table tr.fx-head td { background: #f2eddd; color: #7a6a2f; font-weight: bold; font-size: 8.8px;
                                  text-transform: uppercase; letter-spacing: .3px; }
        .fx-table tr.fx-head .ar { float: right; text-transform: none; letter-spacing: 0; font-weight: normal; direction: rtl; }
        .fx-table tr.fx-cols td { background: #f9f6ee; color: #8a8060; font-weight: bold; font-size: 8px;
                                  text-transform: uppercase; letter-spacing: .3px; text-align: center; }
        .fx-table tr.fx-sign td { background: #f9f6ee; color: #7a6a2f; font-weight: bold; font-size: 8.4px;
                                  text-transform: uppercase; letter-spacing: .3px; }
        .fx-table tr { page-break-inside: avoid; break-inside: avoid; }
        .fx-img { width: 54px; height: 40px; object-fit: cover; border: 1px solid #ccc6b0; border-radius: 2px; display: block; margin: 0 auto; }
        .fx-noimg { color: #b3ac93; }
        .fx-sig-img { max-height: 30px; max-width: 100%; }
        .fx-sig-line { border-top: 1px solid #555; font-size: 7.5px; padding-top: 2px; color: #8a8575;
                       text-transform: none; letter-spacing: 0; font-weight: normal; margin: 0 auto; max-width: 130px; }
        .c { text-align: center; }
        .r { text-align: right; }
        .ok { color: #1d7a45; font-weight: bold; }
        .no { color: #bf2f2f; font-weight: bold; }
        .total td { background: #efe9d6; font-weight: bold; }
        /* Handover terms — bilingual clauses written on the booking's Checklist tab.
           They are an annex to the signed form, so they open a page of their own
           after the acknowledgment. Two columns so a clause's English and Arabic
           wording sit side by side on the same line; the pair never splits across a
           page break. The bodies are the same rich text as the declaration, so they
           borrow .decl. */
        .terms-page { page-break-before: always; break-before: page; }
        .terms-page .sec-split { margin-top: 0; }
        .sec-split { margin: 12px 0 6px; background: #7a6a2f; color: #fff; border-radius: 3px; table-layout: fixed; }
        .sec-split td { padding: 4px 8px; font-size: 10px; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .sec-split .ar { text-align: right; direction: rtl; text-transform: none; letter-spacing: 0; }
        .terms { table-layout: fixed; }
        .terms td { border: 1px solid #ddd; padding: 5px 7px; vertical-align: top;
                    word-wrap: break-word; overflow-wrap: break-word; }
        .terms tr:nth-child(even) td { background: #fcfbf7; }
        .terms tr { page-break-inside: avoid; break-inside: avoid; }
        .terms-t { font-size: 9.4px; font-weight: bold; color: #7a6a2f; margin-bottom: 2px; }
        .terms .ar, .terms .ar .decl { direction: rtl; text-align: right; }
        .terms .decl { margin: 0; }
        /* Signatures repeated under the clauses. Kept whole so a signature never
           lands on a page of its own away from the terms it signs. */
        .terms-sign { margin-top: 14px; page-break-inside: avoid; break-inside: avoid; }
        .accept { border: 1px solid #d8d4c4; border-radius: 4px; padding: 8px 10px; margin-bottom: 8px; }
        .accept .ph { font-size: 11px; font-weight: bold; color: #7a6a2f; text-transform: uppercase; margin-bottom: 4px; }
        /* The declaration is rich text edited in Settings → Rent Out Settings →
           Checklist Notes, so it can carry headings, clause lists and RTL (Arabic)
           paragraphs. These rules keep whatever it holds inside the printed block. */
        .decl { font-size: 8.7px; color: #444; margin: 0 0 8px; line-height: 1.5; }
        .decl > *:first-child { margin-top: 0; }
        .decl > *:last-child { margin-bottom: 0; }
        .decl p { margin: 0 0 5px; }
        /* A blank line the author left stays a breather, not a whole empty line box. */
        .decl p:empty, .decl p:has(> br:only-child) { margin: 0; height: 4px; }
        .decl h1, .decl h2, .decl h3, .decl h4, .decl h5, .decl h6 {
            font-size: 9.4px; font-weight: bold; color: #7a6a2f; margin: 7px 0 3px; text-transform: none; }
        .decl h1, .decl h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .3px; }
        .decl ul, .decl ol { margin: 0 0 5px; padding-left: 14px; }
        .decl ul[dir="rtl"], .decl ol[dir="rtl"] { padding-left: 0; padding-right: 14px; }
        .decl li { margin-bottom: 1px; }
        /* Indented, not quoted: a browser's indent command wraps the line in a
           blockquote, so a quote bar here would mark text the author only indented. */
        .decl blockquote { margin: 0 0 5px; padding-inline-start: 18px; }
        .decl a { color: #444; text-decoration: none; }
        .decl [dir="rtl"] { direction: rtl; text-align: right; }
        .decl table { margin-bottom: 5px; }
        .decl td, .decl th { border: 1px solid #ddd; padding: 2px 4px; }
        /* Fixed layout keeps the three signature cells at a third each — otherwise a long
           signer name widens its cell and pushes the table past the page edge, clipping
           the last signatory. break-word wraps the name inside its third instead. */
        /* The acknowledgment reads as one statement: its declaration and the signatures
           under it move to the next page together rather than splitting across the
           break. (A declaration taller than a whole page still has to break — nothing
           can keep that together.) */
        .accept { page-break-inside: avoid; break-inside: avoid; }
        .items tr, .decl li { page-break-inside: avoid; }
        .decl h1, .decl h2, .decl h3, .decl h4 { page-break-after: avoid; }
        .sign-table { table-layout: fixed; width: 100%; page-break-inside: avoid; }
        /* Top-aligned so every signature rule sits on the same line — a name that wraps
           to two lines grows downwards instead of lifting its own rule. */
        .sign-cell { width: 33.33%; vertical-align: top; padding: 4px 6px; text-align: center;
                     word-wrap: break-word; overflow-wrap: break-word; }
        .sign-img { max-width: 100%; height: 46px; margin-bottom: 2px; }
        .sign-line { border-top: 1px solid #555; padding-top: 3px; font-size: 8.5px; }
        .sign-name { font-weight: bold; font-size: 9px; }
        .muted { color: #8a8575; }
        /* The move-out photo sits under the move-out mark. */
        .mo-img { display: block; width: 30px; height: 30px; object-fit: cover; margin: 2px auto 0; border: 1px solid #ccc6b0; border-radius: 2px; }
</style>
