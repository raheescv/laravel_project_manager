import 'package:flutter/material.dart';

/// Shown when the boot sequence in `main()` throws.
///
/// Deliberately dependency-free: it renders before the service locator, theme
/// and router exist, so it cannot use `context.astra`, `ui()` or anything that
/// assumes a successful boot. Its whole job is to replace a blank white screen
/// with the reason and a way out.
class BootFailureApp extends StatelessWidget {
  const BootFailureApp({super.key, required this.error});

  final Object error;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      home: Scaffold(
        backgroundColor: const Color(0xFF0C1E1A),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.warning_amber_rounded,
                      size: 44, color: Color(0xFFE8C36A)),
                  const SizedBox(height: 16),
                  const Text(
                    "Invo couldn't start",
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'The app failed while starting up. Close it and open it '
                    'again. If it keeps happening, show this screen to support.',
                    style: TextStyle(color: Color(0xFFA9C2BA), fontSize: 14),
                  ),
                  const SizedBox(height: 20),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.06),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: SelectableText(
                      '$error',
                      style: const TextStyle(
                          color: Color(0xFFCFE3DC),
                          fontSize: 12,
                          fontFamily: 'monospace'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
