import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Lets the user point the app at their Laravel server (base URL + tenant).
class ConnectionSheet {
  static Future<void> show(BuildContext context) {
    final auth = context.read<AuthCubit>();
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ConnectionSheetBody(auth: auth),
    );
  }
}

/// Stateful so the controllers live exactly as long as the sheet's own element.
/// Disposing them from `showModalBottomSheet(...).whenComplete` instead fires
/// while the dismissal animation is still rebuilding the fields, which throws
/// "A TextEditingController was used after being disposed".
class _ConnectionSheetBody extends StatefulWidget {
  const _ConnectionSheetBody({required this.auth});

  final AuthCubit auth;

  @override
  State<_ConnectionSheetBody> createState() => _ConnectionSheetBodyState();
}

class _ConnectionSheetBodyState extends State<_ConnectionSheetBody> {
  late final _urlCtl = TextEditingController(text: widget.auth.config.baseUrl);
  late final _tenantCtl = TextEditingController(text: widget.auth.config.tenant);

  @override
  void dispose() {
    _urlCtl.dispose();
    _tenantCtl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final auth = widget.auth;
    final url = _urlCtl.text.trim();
    await auth.updateConnection(
      baseUrl: url.isEmpty ? auth.config.baseUrl : url,
      tenant: _tenantCtl.text.trim(),
    );
    if (mounted) Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(
          color: p.sheet,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 42,
                height: 5,
                decoration: BoxDecoration(
                  color: p.hairline,
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const SectionLabel('Connection'),
            const SizedBox(height: 4),
            Text('Server', style: serif(size: 22, color: p.ink)),
            const SizedBox(height: 16),
            _field('Base URL', _urlCtl,
                hint: 'https://your-salon.com', keyboard: TextInputType.url),
            const SizedBox(height: 12),
            _field('Tenant subdomain', _tenantCtl, hint: 'demo (optional)'),
            const SizedBox(height: 20),
            AstraButton(label: 'Save', icon: Icons.check, onTap: _save),
          ],
        ),
      ),
    );
  }

  /// [keyboard] is `TextInputType.url` for the Base URL so the OS keypad puts
  /// `.` `/` `:` (and `.com`) on the primary layer. Autocorrect/capitalisation
  /// are off on every field here — both mangle a hand-typed host.
  Widget _field(String label, TextEditingController c,
      {String? hint, TextInputType? keyboard}) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(),
            style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        TextField(
          controller: c,
          keyboardType: keyboard ?? TextInputType.text,
          autocorrect: false,
          enableSuggestions: false,
          textCapitalization: TextCapitalization.none,
          style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: p.card,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(14),
              borderSide: BorderSide.none,
            ),
          ),
        ),
      ],
    );
  }
}
