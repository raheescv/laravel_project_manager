import 'dart:async';

import 'package:flutter/material.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Name + Mobile fields for the client sheet that suggest existing customers
/// as the cashier types. Typing in Name searches by name, typing in Mobile
/// searches by number; tapping a suggestion fills both. Goes through the
/// offline-first lookup, so a returning client is still found without a
/// connection (and isn't re-created as a duplicate when the sale syncs).
class ClientLookupFields extends StatefulWidget {
  const ClientLookupFields({super.key, required this.nameCtl, required this.mobileCtl});
  final TextEditingController nameCtl;
  final TextEditingController mobileCtl;

  @override
  State<ClientLookupFields> createState() => _ClientLookupFieldsState();
}

enum _Field { name, mobile }

class _ClientLookupFieldsState extends State<ClientLookupFields> {
  final FocusNode _nameFocus = FocusNode();
  final FocusNode _mobileFocus = FocusNode();
  Timer? _debounce;
  int _query = 0;
  _Field? _active;
  String _term = '';
  bool _loading = false;
  List<Customer> _results = const [];

  @override
  void dispose() {
    _debounce?.cancel();
    _nameFocus.dispose();
    _mobileFocus.dispose();
    super.dispose();
  }

  void _onTyped(_Field field, String value) {
    _debounce?.cancel();
    final term = value.trim();
    final minLength = field == _Field.mobile ? 3 : 2;
    if (term.length < minLength) {
      _query++;
      setState(() {
        _active = field;
        _term = term;
        _loading = false;
        _results = const [];
      });
      return;
    }
    setState(() {
      _active = field;
      _term = term;
      _loading = true;
    });
    _debounce = Timer(const Duration(milliseconds: 250), () => _search(field, term));
  }

  Future<void> _search(_Field field, String term) async {
    final token = ++_query;
    List<Customer> found;
    try {
      found = await serviceLocator<LookupRepository>().customers(
        mobile: field == _Field.mobile ? term : null,
        search: field == _Field.name ? term : null,
      );
    } catch (_) {
      found = const [];
    }
    // A slower, older response must not overwrite what the newer keystroke found.
    if (!mounted || token != _query) return;
    setState(() {
      _loading = false;
      _results = found.where((c) => c.name != AppStrings.walkInCustomer).take(5).toList();
    });
  }

  void _pick(Customer c) {
    _query++;
    _debounce?.cancel();
    widget.nameCtl.text = c.name;
    widget.mobileCtl.text = c.mobile;
    FocusScope.of(context).unfocus();
    setState(() {
      _active = null;
      _loading = false;
      _results = const [];
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _field(_Field.name, 'Name', widget.nameCtl, _nameFocus, hint: AppStrings.walkInCustomer),
        if (_active == _Field.name) _suggestions(),
        const SizedBox(height: 12),
        _field(_Field.mobile, 'Mobile', widget.mobileCtl, _mobileFocus, hint: 'Optional', number: true),
        if (_active == _Field.mobile) _suggestions(),
      ],
    );
  }

  Widget _field(_Field field, String label, TextEditingController c, FocusNode focus, {String? hint, bool number = false}) {
    final p = context.astra;
    final busy = _loading && _active == field;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        TextField(
          controller: c,
          focusNode: focus,
          keyboardType: number ? TextInputType.phone : TextInputType.text,
          textInputAction: number ? TextInputAction.done : TextInputAction.next,
          style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
          onChanged: (v) => _onTyped(field, v),
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: p.card,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
            suffixIcon: busy
                ? Padding(
                    padding: const EdgeInsets.all(15),
                    child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: p.primary)),
                  )
                : Icon(Icons.search, size: 18, color: p.textMuted),
          ),
        ),
      ],
    );
  }

  Widget _suggestions() {
    final p = context.astra;
    if (_results.isEmpty) {
      if (_loading || _term.isEmpty) return const SizedBox.shrink();
      final minLength = _active == _Field.mobile ? 3 : 2;
      if (_term.length < minLength) return const SizedBox.shrink();
      return Padding(
        padding: const EdgeInsets.fromLTRB(4, 8, 4, 0),
        child: Row(
          children: [
            Icon(Icons.person_add_alt_1_outlined, size: 15, color: p.textMuted),
            const SizedBox(width: 6),
            Expanded(
              child: Text('No matching client',
                  style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
            ),
          ],
        ),
      );
    }
    return Container(
      margin: const EdgeInsets.only(top: 6),
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: p.hairline),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      child: Column(
        children: [
          for (var i = 0; i < _results.length; i++) ...[
            if (i > 0) Divider(height: 1, thickness: 1, color: p.hairline, indent: 56),
            _row(_results[i]),
          ],
        ],
      ),
    );
  }

  Widget _row(Customer c) {
    final p = context.astra;
    final initial = c.name.trim().isEmpty ? '?' : c.name.trim()[0].toUpperCase();
    final byName = _active == _Field.name;
    return InkWell(
      onTap: () => _pick(c),
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: p.tint, shape: BoxShape.circle),
              child: Text(initial, style: ui(size: 13, weight: FontWeight.w800, color: p.primary)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _highlighted(c.name, byName ? _term : '', ui(size: 13.5, weight: FontWeight.w700, color: p.ink), p),
                  if (c.mobile.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    _highlighted(c.mobile, byName ? '' : _term, ui(size: 12, weight: FontWeight.w600, color: p.textSecondary), p),
                  ],
                ],
              ),
            ),
            Icon(Icons.north_west, size: 15, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  /// [text] with the first case-insensitive occurrence of [match] in bold accent.
  Widget _highlighted(String text, String match, TextStyle style, AstraPalette p) {
    final at = match.isEmpty ? -1 : text.toLowerCase().indexOf(match.toLowerCase());
    if (at < 0) return Text(text, style: style, maxLines: 1, overflow: TextOverflow.ellipsis);
    return Text.rich(
      TextSpan(style: style, children: [
        TextSpan(text: text.substring(0, at)),
        TextSpan(
          text: text.substring(at, at + match.length),
          style: style.copyWith(color: p.primary, fontWeight: FontWeight.w800),
        ),
        TextSpan(text: text.substring(at + match.length)),
      ]),
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
    );
  }
}
