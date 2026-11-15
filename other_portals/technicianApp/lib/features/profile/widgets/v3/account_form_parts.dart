import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// Shared pieces of the account forms (edit profile, change PIN / password),
/// so the three read as one family on phone and tablet alike.

/// The tinted guidance strip at the top of a form.
class AccountHint extends StatelessWidget {
  const AccountHint({super.key, required this.text, this.icon = Icons.shield_outlined});
  final String text;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: p.tint,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: p.primary.withValues(alpha: 0.18)),
      ),
      child: Row(children: [
        Icon(icon, size: 16, color: p.primary),
        const SizedBox(width: 11),
        Expanded(
          child: Text(text, style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary, height: 1.35)),
        ),
      ]),
    );
  }
}

/// A labelled secret field — digits-only PIN or a password with a reveal eye.
class AccountSecretField extends StatelessWidget {
  const AccountSecretField({
    super.key,
    required this.label,
    required this.controller,
    this.pin = false,
    this.obscure = true,
    this.onToggleObscure,
  });
  final String label;
  final TextEditingController controller;
  final bool pin;
  final bool obscure;
  final VoidCallback? onToggleObscure;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 7),
        Container(
          decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(14), boxShadow: t.softShadow),
          child: TextField(
            controller: controller,
            obscureText: pin || obscure,
            keyboardType: pin ? TextInputType.number : TextInputType.visiblePassword,
            maxLength: pin ? 6 : null,
            inputFormatters: pin ? [FilteringTextInputFormatter.digitsOnly] : null,
            style: ui(size: pin ? 16 : 15, weight: FontWeight.w700, color: p.ink, letterSpacing: pin ? 4 : 0),
            decoration: InputDecoration(
              counterText: '',
              prefixIcon: Icon(Icons.lock_outline, color: p.textMuted, size: 18),
              suffixIcon: pin
                  ? null
                  : IconButton(
                      icon: Icon(obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                          color: p.textMuted, size: 18),
                      onPressed: onToggleObscure,
                    ),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
        ),
      ],
    );
  }
}

/// An inline form error — the danger tint strip with the message.
class AccountError extends StatelessWidget {
  const AccountError({super.key, required this.message});
  final String message;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(color: p.dangerTint, borderRadius: BorderRadius.circular(12)),
      child: Row(children: [
        const Icon(Icons.error_outline, size: 16, color: ColorManager.danger),
        const SizedBox(width: 8),
        Expanded(child: Text(message, style: ui(size: 12, weight: FontWeight.w700, color: ColorManager.danger))),
      ]),
    );
  }
}

void showAccountSnack(BuildContext context, String message) => ScaffoldMessenger.of(context)
  ..hideCurrentSnackBar()
  ..showSnackBar(SnackBar(content: Text(message)));
