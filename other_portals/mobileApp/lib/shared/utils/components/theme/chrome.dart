/// How the tablet window is put together: where the side-rail sits, and how the
/// content beside it is framed.
///
/// This exists because the rail and the panes were drawn in two different
/// idioms — the rail a floating rounded card (a margin, a 26pt radius, a heavy
/// shadow), the panes docked flush with square corners — and the two met on one
/// edge, where the rail's rounded shoulder cut into a pane drawn as though the
/// rail were not there. Every option below resolves that seam a different way;
/// none of them leaves it unresolved. See the preview in
/// `docs/mobile-tablet-chrome-preview.html`.
///
/// Device-local, like the colour preset and the type pairing: a counter tablet
/// bolted to a till and a manager's iPad are the same build with different
/// opinions. Phones never read it — there is no rail to frame.
enum AstraChrome {
  /// The rail is flush and dark to the edges, and everything to its right lifts
  /// into one rounded surface floating on it with an even gutter. The corner
  /// reads as deliberate because the gutter never breaks.
  insetCanvas('inset_canvas', 'Inset canvas', 'Content lifts off a full-height rail'),

  /// The rail stops floating and runs corner to corner; the panes dock against
  /// it. Nothing is rounded against anything, so there is no corner to resolve
  /// — and the margin comes back as content width.
  docked('docked', 'Docked rail', 'Rail and panes, edge to edge'),

  /// Rail and content both float, on the same inset and the same radius. The
  /// two shapes stop arguing because they match.
  peers('peers', 'Two peers', 'Rail and content both float'),

  /// Rail and content in one rounded card split by a hairline, framed by the
  /// canvas. Only the outer corners are rounded.
  unified('unified', 'Unified shell', 'One card, split inside');

  const AstraChrome(this.id, this.label, this.tagline);

  final String id;
  final String label;

  /// One line under the label in the picker — what the eye will actually see.
  final String tagline;

  /// Whether the rail paints all the way to the window's edges in this chrome.
  /// The three that do have to take the status-bar inset themselves, since the
  /// shell can no longer hold it off for them.
  bool get railIsFlush => this != AstraChrome.peers;

  /// Whether the strip behind the status bar is the rail's dark surface rather
  /// than the page canvas — which decides whether the clock needs light icons.
  bool get darkStatusStrip => this == AstraChrome.insetCanvas || this == AstraChrome.docked;

  /// The default on a fresh install.
  static const AstraChrome fallback = AstraChrome.insetCanvas;

  /// Resolve a persisted id. Anything unrecognised — an older build, a
  /// hand-edited pref — falls back rather than throwing.
  static AstraChrome byId(String? id) =>
      AstraChrome.values.firstWhere((c) => c.id == id, orElse: () => fallback);
}
