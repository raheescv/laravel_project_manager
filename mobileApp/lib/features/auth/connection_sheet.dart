import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../state/auth_controller.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';

/// Lets the user point the app at their Laravel server (base URL + tenant).
class ConnectionSheet {
  static Future<void> show(BuildContext context) {
    final auth = context.read<AuthController>();
    final urlCtl = TextEditingController(text: auth.config.baseUrl);
    final tenantCtl = TextEditingController(text: auth.config.tenant);
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final p = ctx.astra;
        return Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
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
                      color: const Color(0xFFDCD6C7),
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const SectionLabel('Connection'),
                const SizedBox(height: 4),
                Text('Server', style: serif(size: 22, color: p.ink)),
                const SizedBox(height: 16),
                _field(ctx, 'Base URL', urlCtl, hint: 'https://your-salon.com'),
                const SizedBox(height: 12),
                _field(ctx, 'Tenant subdomain', tenantCtl, hint: 'demo (optional)'),
                const SizedBox(height: 20),
                AstraButton(
                  label: 'Save',
                  icon: Icons.check,
                  onTap: () async {
                    await auth.updateConnection(
                      baseUrl: urlCtl.text.trim().isEmpty
                          ? auth.config.baseUrl
                          : urlCtl.text.trim(),
                      tenant: tenantCtl.text.trim(),
                    );
                    if (ctx.mounted) Navigator.pop(ctx);
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  static Widget _field(BuildContext ctx, String label, TextEditingController c, {String? hint}) {
    final p = ctx.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(),
            style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        TextField(
          controller: c,
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
