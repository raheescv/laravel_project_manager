import 'package:flutter/material.dart';

import '../../widgets/v3/account_secret_form.dart';

/// Change MPIN — wired to POST /change-pin through `ProfileCubit`. On a tablet
/// it normally runs [embedded] in the Profile / Settings detail pane.
class ChangePinScreen extends StatelessWidget {
  const ChangePinScreen({super.key, this.embedded = false, this.onDone});

  /// Render only the form body — the host supplies the pane and its head.
  final bool embedded;

  /// Where "done" goes when there is no route to pop. Ignored unless [embedded].
  final VoidCallback? onDone;

  @override
  Widget build(BuildContext context) => AccountSecretForm(
        title: 'Change MPIN',
        subtitle: 'Your 4–6 digit login PIN',
        hint: 'Choose a 4–6 digit PIN you don’t use elsewhere.',
        pin: true,
        minLength: 4,
        tooShort: 'Use at least a 4-digit PIN.',
        mismatch: 'New PIN and confirmation don’t match.',
        submitLabel: 'Update PIN',
        successMessage: 'PIN updated',
        failureMessage: 'Could not update PIN.',
        labels: ('Current PIN', 'New PIN', 'Confirm new PIN'),
        embedded: embedded,
        onDone: onDone,
      );
}
