import 'package:flutter/material.dart';

import '../../widgets/v3/account_secret_form.dart';

/// Change password — wired to POST /change-password through `ProfileCubit`.
/// On a tablet it normally runs [embedded] in the Profile / Settings pane.
class ChangePasswordScreen extends StatelessWidget {
  const ChangePasswordScreen({super.key, this.embedded = false, this.onDone});

  final bool embedded;
  final VoidCallback? onDone;

  @override
  Widget build(BuildContext context) => AccountSecretForm(
        title: 'Change password',
        subtitle: 'Your account login password',
        hint: 'Use at least 8 characters you don’t use elsewhere.',
        pin: false,
        minLength: 8,
        tooShort: 'Use at least an 8-character password.',
        mismatch: 'New password and confirmation don’t match.',
        submitLabel: 'Update password',
        successMessage: 'Password updated',
        failureMessage: 'Could not update password.',
        labels: ('Current password', 'New password', 'Confirm new password'),
        embedded: embedded,
        onDone: onDone,
      );
}
