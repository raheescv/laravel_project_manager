part of 'continuous_scanner_screen.dart';

// The permission-flow state screens (primer / settings / failed / unsupported)
// and the live feed panel. Split out of continuous_scanner_screen.dart; a
// `part`, so these stay library-private and no call site changed.

extension _ScannerStates on _ContinuousScannerScreenState {
  Widget _primerState(AstraPalette p) {
    return _CameraStateView(
      p: p,
      icon: Icons.qr_code_scanner_rounded,
      title: 'Allow camera access',
      message:
          'The camera is used only while this screen is open, to read product barcodes. Nothing is photographed or recorded.',
      primary: _StateAction(Icons.photo_camera_outlined, 'Enable camera', _requestPermission),
      links: [
        _StateAction(null, 'Type codes manually instead', _manualEntry),
        _StateAction(null, 'Go back', _close),
      ],
    );
  }

  Widget _settingsState(AstraPalette p) {
    return _CameraStateView(
      p: p,
      icon: Icons.no_photography_outlined,
      title: 'Camera access is off',
      message:
          'Barcode scanning needs the camera. Turn it on in Settings — this screen will pick it up the moment you come back.',
      steps: _permissionSteps(),
      primary: _StateAction(Icons.settings_outlined, 'Open Settings', () async => openAppSettings()),
      links: [
        _StateAction(null, "I've allowed it — check again", _boot),
        _StateAction(null, 'Type codes manually instead', _manualEntry),
        _StateAction(null, 'Go back', _close),
      ],
    );
  }

  Widget _failedState(AstraPalette p) {
    return _CameraStateView(
      p: p,
      icon: Icons.videocam_off_outlined,
      title: 'Couldn’t start the camera',
      message:
          'This is usually temporary — another app may be using the camera. Close other camera apps and try again, or type the code instead.',
      technical: _lastErrorDetail,
      primary: _StateAction(Icons.refresh_rounded, 'Try again', _attach),
      links: [
        _StateAction(null, 'Type the code instead', _manualEntry),
        _StateAction(null, 'Go back', _close),
      ],
    );
  }

  Widget _unsupportedState(AstraPalette p) {
    return _CameraStateView(
      p: p,
      icon: Icons.keyboard_alt_outlined,
      title: 'Scanning not supported',
      message:
          'This device can’t scan barcodes with the camera. You can still add items by typing their codes.',
      primary: _StateAction(Icons.keyboard_alt_outlined, 'Enter code', _manualEntry),
      links: [_StateAction(null, 'Go back', _close)],
    );
  }

  Widget _feedPanel(AstraPalette p) {
    return Container(
      color: const Color(0xFF0F0F0F),
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 0),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 132,
              child: _feed.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: Text(
                          widget.emptyHint,
                          textAlign: TextAlign.center,
                          style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white54, height: 1.5),
                        ),
                      ),
                    )
                  : ListView.builder(
                      itemCount: _feed.length,
                      itemBuilder: (_, i) {
                        final l = _feed[i];
                        final top = i == 0;
                        final c = _lineColor(l);
                        final showPlus = l.fb.isOk && !l.undone;
                        return Container(
                          margin: EdgeInsets.only(bottom: top ? 10 : 0),
                          padding: EdgeInsets.symmetric(vertical: top ? 11 : 8, horizontal: top ? 12 : 2),
                          decoration: top
                              ? BoxDecoration(
                                  gradient: LinearGradient(colors: [p.primaryDark, Color.lerp(Colors.black, p.primaryDark, 0.6)!]),
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(color: c.withValues(alpha: 0.4)),
                                )
                              : BoxDecoration(border: Border(top: BorderSide(color: Colors.white.withValues(alpha: 0.06)))),
                          child: Row(
                            children: [
                              Container(
                                width: top ? 36 : 22,
                                height: top ? 36 : 22,
                                decoration: BoxDecoration(color: c.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(top ? 11 : 7)),
                                child: Icon(_lineIcon(l), size: top ? 18 : 12, color: c),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(l.fb.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: top ? 12.5 : 11.5, weight: FontWeight.w800, color: Colors.white)),
                                    if (top && l.fb.detail != null) ...[const SizedBox(height: 2), Text(l.fb.detail!, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10, weight: FontWeight.w600, color: Colors.white70))],
                                  ],
                                ),
                              ),
                              if (showPlus && !top) Text('+1', style: ui(size: 11, weight: FontWeight.w800, color: c)),
                              if (showPlus && top) Text('+1', style: serif(size: 20, color: c)),
                            ],
                          ),
                        );
                      },
                    ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 10),
              child: Row(
                children: [
                  _panelBtn(icon: Icons.undo, label: 'Undo', enabled: _undoable != null && !_busy, onTap: _undo),
                  const SizedBox(width: 9),
                  _panelBtn(icon: Icons.keyboard_alt_outlined, label: 'Type', enabled: !_busy, onTap: _manualEntry),
                  const SizedBox(width: 9),
                  Expanded(
                    child: Semantics(
                      button: true,
                      label: 'Done — finish scanning',
                      child: GestureDetector(
                        onTap: _close,
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          alignment: Alignment.center,
                          decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(15)),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.check_rounded, size: 17, color: Colors.white),
                              const SizedBox(width: 8),
                              Text('Done', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _panelBtn({required IconData icon, required String label, required bool enabled, required VoidCallback onTap}) {
    return Semantics(
      button: true,
      enabled: enabled,
      label: label,
      child: Opacity(
        opacity: enabled ? 1 : 0.4,
        child: GestureDetector(
          onTap: enabled ? onTap : null,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(15),
              border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 18, color: Colors.white),
                const SizedBox(height: 3),
                Text(label, style: ui(size: 9.5, weight: FontWeight.w800, color: Colors.white70)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _circleBtn(IconData icon, VoidCallback onTap, {bool gold = false, String? semantic}) {
    final p = context.astra;
    return Semantics(
      button: true,
      label: semantic,
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          width: 44, height: 44,
          decoration: BoxDecoration(color: gold ? p.accent : Colors.white.withValues(alpha: 0.16), shape: BoxShape.circle, border: Border.all(color: Colors.white.withValues(alpha: 0.22))),
          child: Icon(icon, color: gold ? p.primaryDark : Colors.white, size: 19),
        ),
      ),
    );
  }
}
