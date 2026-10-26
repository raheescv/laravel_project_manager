import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:uuid/uuid.dart';

import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/logic/token_cubit/token_cubit.dart';
import 'package:invo/features/token/widgets/v3/token_hero_band.dart';
import 'package:invo/features/token/widgets/v3/token_slip_view.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Settings → Token. Everything here is device-local and click-and-go: each
/// change saves at once and the slip at the top redraws with it.
class TokenSettingsScreen extends StatefulWidget {
  const TokenSettingsScreen({super.key});

  @override
  State<TokenSettingsScreen> createState() => _TokenSettingsScreenState();
}

class _TokenSettingsScreenState extends State<TokenSettingsScreen> {
  late final TokenSettings _initial = context.read<TokenCubit>().state.settings;
  late final _prefix = TextEditingController(text: _initial.prefix);
  late final _separator = TextEditingController(text: _initial.separator);
  late final _startAt = TextEditingController(text: '${_initial.startAt}');
  late final _deviceTag = TextEditingController(text: _initial.deviceTag);
  late final _header = TextEditingController(text: _initial.header);
  late final _footerEn = TextEditingController(text: _initial.footerEnglish);
  late final _footerAr = TextEditingController(text: _initial.footerArabic);

  @override
  void dispose() {
    for (final c in [_prefix, _separator, _startAt, _deviceTag, _header, _footerEn, _footerAr]) {
      c.dispose();
    }
    super.dispose();
  }

  TokenCubit get _cubit => context.read<TokenCubit>();

  void _update(TokenSettings Function(TokenSettings s) change) => _cubit.updateSettings(change(_cubit.state.settings));

  Future<void> _editCounter([TokenCounter? counter]) async {
    final p = context.astra;
    final saved = await showModalBottomSheet<TokenCounter>(
      context: context,
      isScrollControlled: true,
      backgroundColor: p.card,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (_) => _CounterSheet(counter: counter),
    );
    if (saved == null) return;
    _update((s) {
      final exists = s.counters.any((c) => c.id == saved.id);
      return s.copyWith(
          counters: exists ? [for (final c in s.counters) c.id == saved.id ? saved : c] : [...s.counters, saved]);
    });
  }

  void _removeCounter(TokenCounter counter) =>
      _update((s) => s.copyWith(counters: s.counters.where((c) => c.id != counter.id).toList()));

  Future<void> _confirmReset() async {
    final p = context.astra;
    final snack = AstraSnack.capture(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: p.card,
        title: Text('Reset today\'s counter?', style: ui(size: 16, weight: FontWeight.w800, color: p.ink)),
        content: Text(
          'The next token starts again from ${_cubit.state.settings.startAt} on every counter. '
          'Numbers already printed today can come up again.',
          style: ui(size: 12.5, weight: FontWeight.w500, color: p.textSecondary, height: 1.45),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Reset', style: ui(size: 14, weight: FontWeight.w800, color: AstraPalette.danger)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    await _cubit.resetToday();
    snack.success('Counter reset. The next token is ${_cubit.state.nextDisplayNo}');
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final s = context.watch<TokenCubit>().state;
    final print = context.watch<PrintSettingsCubit>();
    final cfg = s.settings;

    final preview = <Widget>[
        Center(
          child: SizedBox(
            width: context.isTablet ? 300 : 230,
            child: Container(
              decoration: BoxDecoration(boxShadow: [
                BoxShadow(color: Colors.black.withValues(alpha: 0.10), blurRadius: 20, offset: const Offset(0, 10)),
              ]),
              child: FittedBox(
                child: TokenSlipView(
                  displayNo: s.nextDisplayNo,
                  counterName: s.counter?.name ?? '',
                  settings: cfg,
                  paper: print.width,
                  logo: print.state.logoBytes,
                  companyName: print.companyName,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Text('Live preview · ${print.width.label} roll',
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
        ),
    ];
    final numberSection = <Widget>[
        const SectionLabel('Number'),
        const SizedBox(height: 8),
        AstraCard(
          radius: 16,
          child: Column(
            children: [
              Row(children: [
                Expanded(
                  child: _Field(
                    label: cfg.hasCounters ? 'Prefix (no counters)' : 'Prefix',
                    controller: _prefix,
                    hint: 'e.g. A',
                    maxLength: 4,
                    onChanged: (v) => _update((s) => s.copyWith(prefix: v.trim())),
                  ),
                ),
                const SizedBox(width: 10),
                SizedBox(
                  width: 92,
                  child: _Field(
                    label: 'Separator',
                    controller: _separator,
                    hint: 'none',
                    maxLength: 2,
                    onChanged: (v) => _update((s) => s.copyWith(separator: v)),
                  ),
                ),
              ]),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(
                  child: _Field(
                    label: 'Start at',
                    controller: _startAt,
                    hint: '1',
                    maxLength: 5,
                    digitsOnly: true,
                    onChanged: (v) => _update((s) => s.copyWith(startAt: int.tryParse(v) ?? 1)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _Field(
                    label: 'Device tag',
                    controller: _deviceTag,
                    hint: 'e.g. K1',
                    maxLength: 4,
                    onChanged: (v) => _update((s) => s.copyWith(deviceTag: v.trim())),
                  ),
                ),
              ]),
              const SizedBox(height: 14),
              _Segment<int>(
                label: 'Digits',
                options: const [2, 3, 4, 5],
                selected: cfg.digits,
                labelOf: (d) => '${'0' * (d - 1)}7',
                onSelect: (d) => _update((s) => s.copyWith(digits: d)),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(4, 8, 4, 0),
          child: Text(
            'Numbers restart from "Start at" every day. Each device counts on its own, so give each kiosk a different device tag.',
            style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted, height: 1.4),
          ),
        ),
    ];
    final counterSection = <Widget>[
        SectionLabel('Counters',
            trailing: GestureDetector(
              onTap: _editCounter,
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.add, size: 15, color: p.primary),
                const SizedBox(width: 3),
                Text('Add', style: ui(size: 12, weight: FontWeight.w800, color: p.primary)),
              ]),
            )),
        const SizedBox(height: 8),
        if (!cfg.hasCounters)
          AstraCard(
            radius: 14,
            onTap: _editCounter,
            child: Text('No counters, so this device issues one number stream. Add counters like Cashier or Service to give each its own prefix and sequence.',
                style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted, height: 1.4)),
          )
        else
          for (final c in cfg.counters)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: AstraCard(
                radius: 14,
                padding: const EdgeInsets.fromLTRB(14, 10, 6, 10),
                onTap: () => _editCounter(c),
                child: Row(children: [
                  Container(
                    width: 34,
                    height: 34,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(10)),
                    child: Text(c.prefix.isEmpty ? '—' : c.prefix,
                        style: ui(size: 13, weight: FontWeight.w800, color: p.primary)),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(c.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                      Text('next ${cfg.format(s.ledger.nextSequence(c.id, cfg.startAt), counter: c)}',
                          style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                    ]),
                  ),
                  IconButton(
                    onPressed: () => _removeCounter(c),
                    icon: Icon(Icons.delete_outline, size: 19, color: p.textMuted),
                  ),
                ]),
              ),
            ),
    ];
    final slipSection = <Widget>[
        const SectionLabel('Slip'),
        const SizedBox(height: 8),
        AstraCard(
          radius: 16,
          child: Column(
            children: [
              _Field(
                label: 'Header line',
                controller: _header,
                hint: 'Optional, printed under the logo',
                onChanged: (v) => _update((s) => s.copyWith(header: v)),
              ),
              const SizedBox(height: 12),
              _Field(
                label: 'Footer (English)',
                controller: _footerEn,
                hint: 'Optional',
                onChanged: (v) => _update((s) => s.copyWith(footerEnglish: v)),
              ),
              const SizedBox(height: 12),
              _Field(
                label: 'Footer (Arabic)',
                controller: _footerAr,
                hint: 'اختياري',
                rtl: true,
                onChanged: (v) => _update((s) => s.copyWith(footerArabic: v)),
              ),
              const SizedBox(height: 14),
              _Segment<TokenNumberSize>(
                label: 'Number size',
                options: TokenNumberSize.values,
                selected: cfg.numberSize,
                labelOf: (z) => z.label,
                onSelect: (z) => _update((s) => s.copyWith(numberSize: z)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 9),
        _Toggle(
          icon: Icons.image_outlined,
          title: 'Logo',
          subtitle: 'The company logo from Printer & Receipt',
          value: cfg.showLogo,
          onChanged: (v) => _update((s) => s.copyWith(showLogo: v)),
        ),
        const SizedBox(height: 9),
        _Toggle(
          icon: Icons.schedule,
          title: 'Date & time',
          subtitle: 'When the token was issued',
          value: cfg.showDateTime,
          onChanged: (v) => _update((s) => s.copyWith(showDateTime: v)),
        ),
        const SizedBox(height: 9),
        _Toggle(
          icon: Icons.bolt_outlined,
          title: 'Auto-print on issue',
          subtitle: 'Print the slip as soon as New Token is tapped',
          value: cfg.autoPrint,
          onChanged: (v) => _update((s) => s.copyWith(autoPrint: v)),
        ),
    ];
    final todaySection = <Widget>[
        const SectionLabel('Today'),
        const SizedBox(height: 8),
        AstraCard(
          radius: 14,
          onTap: _confirmReset,
          child: Row(children: [
            IconChip(
                icon: Icons.restart_alt,
                size: 32,
                radius: 9,
                bg: p.dangerTint,
                fg: AstraPalette.danger),
            const SizedBox(width: 11),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Reset counter', style: ui(size: 12.5, weight: FontWeight.w700, color: AstraPalette.danger)),
                Text('${s.ledger.issued} issued today · start again from ${cfg.startAt}',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ]),
            ),
            Icon(Icons.chevron_right, size: 18, color: p.textMuted),
          ]),
        ),
    ];

    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: _tablet(context, s, print.width.label,
                preview: preview, left: [...numberSection, const SizedBox(height: 22), ...counterSection],
                right: [...slipSection, const SizedBox(height: 22), ...todaySection]),
          ),
        ),
      );
    }

    final body = ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
      children: [
        ...preview,
        const SizedBox(height: 22),
        ...numberSection,
        const SizedBox(height: 22),
        ...counterSection,
        const SizedBox(height: 22),
        ...slipSection,
        const SizedBox(height: 22),
        ...todaySection,
      ],
    );

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'Token settings',
              subtitle: 'Number format & slip · this device',
              leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => context.pop()),
            ),
            Expanded(child: MaxWidthBox(maxWidth: 560, child: body)),
          ],
        ),
      ),
    );
  }

  /// Tablet: the Status Board frame. The live slip stays pinned on the left
  /// while the settings scroll beside it, in two columns when there is room.
  Widget _tablet(BuildContext context, TokenState s, String paperLabel,
      {required List<Widget> preview, required List<Widget> left, required List<Widget> right}) {
    final p = context.astra;
    return LayoutBuilder(builder: (context, box) {
      final twoColumns = box.maxWidth >= 1000;
      final previewWidth = box.maxWidth >= 900 ? 360.0 : 290.0;
      final form = twoColumns
          ? Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: left)),
                const SizedBox(width: 18),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: right)),
              ],
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [...left, const SizedBox(height: 22), ...right],
            );
      return Padding(
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TokenHeroBand(
              icon: Icons.tune,
              overline: 'QUEUE TOKENS · THIS DEVICE',
              title: 'Token settings',
              subtitle: 'Number format, counters & slip · saved as you type',
              meta: [
                (label: 'Next token', value: s.nextDisplayNo, gold: true),
                (label: 'Paper', value: paperLabel, gold: false),
              ],
              onBack: context.canPop() ? () => context.pop() : null,
              compact: box.maxWidth < 820,
            ),
            const SizedBox(height: 14),
            Expanded(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: previewWidth,
                    child: AstraCard(
                      radius: 22,
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 22),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const SectionLabel('Live preview'),
                          const SizedBox(height: 18),
                          ...preview,
                          const SizedBox(height: 14),
                          Text('Every change saves at once and shows here first.',
                              textAlign: TextAlign.center,
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted, height: 1.4)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(width: 18),
                  Expanded(
                    child: SingleChildScrollView(
                      padding: EdgeInsets.only(bottom: 24 + MediaQuery.paddingOf(context).bottom),
                      child: form,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    });
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.label,
    required this.controller,
    required this.onChanged,
    this.hint = '',
    this.maxLength,
    this.digitsOnly = false,
    this.rtl = false,
  });

  final String label;
  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final String hint;
  final int? maxLength;
  final bool digitsOnly;
  final bool rtl;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
        const SizedBox(height: 5),
        Container(
          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: TextField(
            controller: controller,
            onChanged: onChanged,
            textDirection: rtl ? TextDirection.rtl : TextDirection.ltr,
            keyboardType: digitsOnly ? TextInputType.number : TextInputType.text,
            inputFormatters: [
              if (digitsOnly) FilteringTextInputFormatter.digitsOnly,
              if (maxLength != null) LengthLimitingTextInputFormatter(maxLength),
            ],
            style: ui(size: 13, weight: FontWeight.w700, color: p.ink),
            decoration: InputDecoration(
              border: InputBorder.none,
              isDense: true,
              contentPadding: const EdgeInsets.symmetric(vertical: 11),
              hintText: hint,
              hintStyle: ui(size: 12, weight: FontWeight.w500, color: p.textMuted),
            ),
          ),
        ),
      ],
    );
  }
}

class _Segment<T> extends StatelessWidget {
  const _Segment({
    required this.label,
    required this.options,
    required this.selected,
    required this.labelOf,
    required this.onSelect,
  });

  final String label;
  final List<T> options;
  final T selected;
  final String Function(T) labelOf;
  final ValueChanged<T> onSelect;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Row(
      children: [
        Text(label, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
        const Spacer(),
        Container(
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (final o in options)
                GestureDetector(
                  onTap: () => onSelect(o),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 150),
                    padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                    decoration: BoxDecoration(
                      gradient: o == selected ? p.primaryGradient : null,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(labelOf(o),
                        style: ui(
                            size: 11.5,
                            weight: FontWeight.w800,
                            color: o == selected ? Colors.white : p.textSecondary)),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class _Toggle extends StatelessWidget {
  const _Toggle({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: () => onChanged(!value),
      child: Row(children: [
        IconChip(icon: icon, size: 32, radius: 9, bg: p.tint),
        const SizedBox(width: 11),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
            Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
          ]),
        ),
        AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: 44,
          height: 26,
          padding: const EdgeInsets.all(3),
          alignment: value ? Alignment.centerRight : Alignment.centerLeft,
          decoration: BoxDecoration(
            gradient: value ? p.primaryGradient : null,
            color: value ? null : p.hairline,
            borderRadius: BorderRadius.circular(20),
          ),
          child: Container(
            width: 20,
            height: 20,
            decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
          ),
        ),
      ]),
    );
  }
}

/// Add / edit one counter. Owns its controllers so they live exactly as long
/// as the sheet.
class _CounterSheet extends StatefulWidget {
  const _CounterSheet({this.counter});

  final TokenCounter? counter;

  @override
  State<_CounterSheet> createState() => _CounterSheetState();
}

class _CounterSheetState extends State<_CounterSheet> {
  late final _name = TextEditingController(text: widget.counter?.name ?? '');
  late final _prefix = TextEditingController(text: widget.counter?.prefix ?? '');

  @override
  void dispose() {
    _name.dispose();
    _prefix.dispose();
    super.dispose();
  }

  void _save() {
    final name = _name.text.trim();
    final prefix = _prefix.text.trim();
    if (name.isEmpty && prefix.isEmpty) return;
    final base = widget.counter ?? TokenCounter(id: const Uuid().v4(), name: '', prefix: '');
    Navigator.of(context).pop(base.copyWith(name: name, prefix: prefix));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(widget.counter == null ? 'New counter' : 'Edit counter',
              style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(height: 2),
          Text('Each counter keeps its own prefix and its own daily sequence.',
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
          const SizedBox(height: 14),
          Row(children: [
            Expanded(child: _Field(label: 'Name', controller: _name, hint: 'e.g. Cashier', onChanged: (_) {})),
            const SizedBox(width: 10),
            SizedBox(
              width: 92,
              child: _Field(label: 'Prefix', controller: _prefix, hint: 'B', maxLength: 4, onChanged: (_) {}),
            ),
          ]),
          const SizedBox(height: 16),
          AstraButton(label: widget.counter == null ? 'Add counter' : 'Save counter', onTap: _save),
        ],
      ),
    );
  }
}
