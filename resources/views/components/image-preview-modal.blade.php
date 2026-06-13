{{--
    Reusable full-size image lightbox — a self-contained overlay (NOT a Bootstrap
    modal), so it can open on top of other Bootstrap modals (e.g. the Add-Items
    modal) without dismissing them.

    Include once per page:  <x-image-preview-modal />
    Trigger from any thumbnail:
        <img src="thumb.jpg" class="zoomable" data-img="{{ $fullUrl }}"
             style="cursor:zoom-in;" title="Click to enlarge">

    The click handler is delegated on `document`, so triggers rendered later by
    Livewire still work without re-binding.
--}}
@once
    <div id="imgZoomOverlay"
        style="display:none; position:fixed; inset:0; z-index:1090; background:rgba(0,0,0,.85);
               align-items:center; justify-content:center; cursor:zoom-out;">
        <span id="imgZoomClose"
            style="position:absolute; top:18px; right:26px; color:#fff; font-size:34px; line-height:1; cursor:pointer;"
            aria-label="Close">&times;</span>
        <img id="imgZoomTarget" src="" alt="Preview"
            style="max-width:92vw; max-height:88vh; object-fit:contain; border-radius:8px; box-shadow:0 12px 40px rgba(0,0,0,.5);">
    </div>
    <script>
        (function () {
            if (window.__imgZoomInit) return;
            window.__imgZoomInit = true;

            document.addEventListener('click', function (e) {
                var img = e.target.closest && e.target.closest('img.zoomable');
                if (img) {
                    e.preventDefault();
                    e.stopPropagation();
                    var overlay = document.getElementById('imgZoomOverlay');
                    var target = document.getElementById('imgZoomTarget');
                    if (!overlay || !target) return;
                    target.src = img.getAttribute('data-img') || img.src;
                    overlay.style.display = 'flex';
                    return;
                }
                if (e.target.id === 'imgZoomOverlay' || e.target.id === 'imgZoomClose') {
                    var ov = document.getElementById('imgZoomOverlay');
                    var tg = document.getElementById('imgZoomTarget');
                    if (ov) ov.style.display = 'none';
                    if (tg) tg.src = '';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var ov = document.getElementById('imgZoomOverlay');
                if (ov && ov.style.display === 'flex') {
                    ov.style.display = 'none';
                    document.getElementById('imgZoomTarget').src = '';
                }
            });
        })();
    </script>
@endonce
