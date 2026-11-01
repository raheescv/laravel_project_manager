import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/image_source_sheet.dart';
import 'package:invo/shared/widgets/nav_scaffold.dart';

import '../../domain/models/catalog_models.dart';
import '../../logic/catalog_form_cubit/catalog_form_cubit.dart';
import '../../logic/catalog_options_cubit/catalog_options_cubit.dart';
import '../../widgets/v3/catalog_controls.dart';
import '../../widgets/v3/catalog_form_parts.dart';
import '../../widgets/v3/option_picker_sheet.dart';

/// Add / Edit a product or service — a full page with the web form's
/// sections: details, purchase and sales information (or, for a service,
/// pricing and duration) and photos. Two columns on a tablet, like the web.
class CatalogFormScreen extends StatelessWidget {
  const CatalogFormScreen({super.key, required this.type, this.id, this.onClose});

  final CatalogType type;

  /// Null for a new item.
  final int? id;

  /// Set when hosted in the home shell, which provides the navigation and keeps
  /// an unfinished form alive across tabs; called on back, save or delete with
  /// whether anything changed. Unset, the page is a pushed route.
  final ValueChanged<bool>? onClose;

  /// Leave the form — through the shell when hosted there, else pop the route.
  static void close(BuildContext context, {bool changed = false}) {
    final onClose = context.findAncestorWidgetOfExactType<CatalogFormScreen>()?.onClose;
    onClose != null ? onClose(changed) : context.pop(changed);
  }

  @override
  Widget build(BuildContext context) {
    final loader = _FormLoader(type: type, id: id);
    return BlocProvider(
      create: (_) => CatalogFormCubit(type: type),
      child: onClose == null ? NavScaffold(child: loader) : loader,
    );
  }
}

class _FormLoader extends StatefulWidget {
  const _FormLoader({required this.type, this.id});

  final CatalogType type;
  final int? id;

  @override
  State<_FormLoader> createState() => _FormLoaderState();
}

class _FormLoaderState extends State<_FormLoader> {
  @override
  void initState() {
    super.initState();
    _load();
  }

  /// A new item needs the options first — it starts on the first department
  /// and the default unit, as on the web.
  Future<void> _load() async {
    final options = context.read<CatalogOptionsCubit>();
    await options.ensureLoaded();
    if (!mounted) return;
    unawaited(context.read<CatalogFormCubit>().load(id: widget.id, blank: CatalogItem.blank(options.state.options)));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return BlocListener<CatalogFormCubit, CatalogFormState>(
      listenWhen: (a, b) => b.errorMessage != null && a.errorMessage != b.errorMessage && b.status == DataFetchStatus.success,
      listener: (context, s) => AstraSnack.error(context, s.errorMessage!),
      child: ColoredBox(
        color: p.canvas,
        child: BlocBuilder<CatalogFormCubit, CatalogFormState>(
          buildWhen: (a, b) => a.session != b.session || a.status != b.status,
          builder: (context, s) {
            if (s.status == DataFetchStatus.failed) {
              return Center(
                child: EmptyState(
                  icon: Icons.cloud_off_outlined,
                  title: s.errorMessage ?? 'Could not load this ${widget.type.label.toLowerCase()}',
                  action: AstraButton(label: 'Try again', icon: Icons.refresh, onTap: _load),
                ),
              );
            }
            if (s.item == null || s.status != DataFetchStatus.success) return const Center(child: CircularProgressIndicator());
            return _CatalogForm(key: ValueKey(s.session), type: widget.type, item: s.item!);
          },
        ),
      ),
    );
  }
}

class _CatalogForm extends StatefulWidget {
  const _CatalogForm({super.key, required this.type, required this.item});

  final CatalogType type;
  final CatalogItem item;

  @override
  State<_CatalogForm> createState() => _CatalogFormState();
}

class _CatalogFormState extends State<_CatalogForm> {
  CatalogItem get _item => widget.item;
  CatalogType get _type => widget.type;

  late final _name = TextEditingController(text: _item.name);
  late final _nameArabic = TextEditingController(text: _item.nameArabic);
  late final _code = TextEditingController(text: _item.code);
  late final _priority = TextEditingController(text: _item.isNew ? '' : '${_item.priority}');
  late final _size = TextEditingController(text: _item.size);
  late final _sizeCategory = TextEditingController(text: _item.sizeCategory);
  late final _hsn = TextEditingController(text: _item.hsnCode);
  late final _tax = TextEditingController(text: _num(_item.tax));
  late final _barcode = TextEditingController(text: _item.barcodeNumber);
  late final _description = TextEditingController(text: _item.description);
  late final _cost = TextEditingController(text: _num(_item.cost));
  late final _mrp = TextEditingController(text: _num(_item.mrp));
  late final _time = TextEditingController(text: _num(_item.time));

  late CatalogRef? _department = _item.department;
  late CatalogRef? _mainCategory = _item.mainCategory;
  late CatalogRef? _subCategory = _item.subCategory;
  late CatalogRef? _unit = _item.unit;
  late CatalogRef? _brand = _item.brand;
  late bool _active = _item.active;
  late bool _selling = _item.isSelling;
  late bool _favorite = _item.isFavorite;

  /// Field → message, shown after a save attempt.
  Map<String, String> _errors = const {};

  List<TextEditingController> get _controllers =>
      [_name, _nameArabic, _code, _priority, _size, _sizeCategory, _hsn, _tax, _barcode, _description, _cost, _mrp, _time];

  static String _num(double v) => v == 0 ? '' : (v == v.roundToDouble() ? v.toInt().toString() : v.toString());
  static double _parse(TextEditingController c) => double.tryParse(c.text.trim()) ?? 0;

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    super.dispose();
  }

  bool get _canSave => context.read<AuthCubit>().hasPermission(_item.isNew ? _type.createPermission : _type.editPermission);
  bool get _canDelete => !_item.isNew && context.read<AuthCubit>().hasPermission(_type.deletePermission);

  CatalogItem _draft() => CatalogItem(
        id: _item.id,
        name: _name.text,
        nameArabic: _nameArabic.text,
        code: _code.text,
        active: _active,
        department: _department,
        mainCategory: _mainCategory,
        subCategory: _subCategory,
        unit: _unit,
        brand: _brand,
        priority: _parse(_priority).toInt(),
        size: _size.text,
        sizeCategory: _sizeCategory.text,
        hsnCode: _hsn.text,
        tax: _parse(_tax),
        barcodeNumber: _barcode.text,
        description: _description.text,
        isSelling: _selling,
        isFavorite: _favorite,
        cost: _parse(_cost),
        mrp: _parse(_mrp),
        time: _parse(_time),
        thumbnail: _item.thumbnail,
        images: _item.images,
      );

  Map<String, String> _validate() => {
        if (_name.text.trim().isEmpty) 'name': 'Enter a name',
        if (_department == null) 'department': 'Choose a department',
        if (_mainCategory == null) 'mainCategory': 'Choose a main category',
        if (_type.isProduct && _unit == null) 'unit': 'Choose a unit',
        if (_mrp.text.trim().isEmpty) 'mrp': 'Enter the price',
        if (_type.isProduct && _cost.text.trim().isEmpty) 'cost': 'Enter the cost',
      };

  /// [another]: "Save & New" — keep the department, categories, unit and brand
  /// for the next one, as the web form does.
  Future<void> _save({bool another = false}) async {
    final errors = _validate();
    setState(() => _errors = errors);
    if (errors.isNotEmpty) {
      AstraSnack.error(context, 'Fill in the highlighted fields.');
      return;
    }
    final cubit = context.read<CatalogFormCubit>();
    final snack = AstraSnack.capture(context);
    final saved = await cubit.save(_draft());
    if (saved == null || !mounted) return;
    snack.success('${saved.name} ${_item.isNew ? 'created' : 'saved'}');
    if (another) {
      unawaited(cubit.load(
          blank: CatalogItem(department: _department, mainCategory: _mainCategory, subCategory: _subCategory, unit: _unit, brand: _brand)));
    } else {
      CatalogFormScreen.close(context, changed: true);
    }
  }

  Future<void> _delete() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('Delete ${_item.name}?'),
        content: const Text('This cannot be undone. Items already used on a sale, return or purchase cannot be deleted.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Delete', style: TextStyle(color: AstraPalette.danger))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    final snack = AstraSnack.capture(context);
    if (await context.read<CatalogFormCubit>().delete() && mounted) {
      snack.success('${_item.name} deleted');
      CatalogFormScreen.close(context, changed: true);
    }
  }

  Future<void> _addPhoto() async {
    final cubit = context.read<CatalogFormCubit>();
    final source = await showImageSourceSheet(context);
    if (source == null) return;
    final file = await ImagePicker().pickImage(source: source, imageQuality: 88, maxWidth: 1600, maxHeight: 1600);
    if (file == null) return;
    await cubit.addPhoto(await file.readAsBytes(), file.name);
  }

  /// Opens a picker over [options] and stores the result via [apply].
  Future<void> _pick(String title, List<CatalogOption> options, CatalogRef? current, void Function(CatalogRef? ref) apply,
      {bool optional = false, bool create = true}) async {
    final result = await showOptionPicker(
      context,
      title: title,
      options: options,
      selectedId: current?.id,
      clearLabel: optional ? 'None' : null,
      allowCreate: create,
    );
    if (result != null && mounted) setState(() => apply(result.ref));
  }

  @override
  Widget build(BuildContext context) {
    final options = context.select<CatalogOptionsCubit, CatalogOptions>((c) => c.state.options);
    final details = _details(options);
    final side = [_preview(), ..._pricing(), _photos()];
    final tablet = context.isTablet;

    final body = tablet
        ? SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(22, 4, 22, 24),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(flex: 11, child: details),
                const SizedBox(width: 16),
                Expanded(flex: 10, child: _stack(side)),
              ],
            ),
          )
        : ListView(
            padding: EdgeInsets.fromLTRB(14, 2, 14, MediaQuery.paddingOf(context).bottom + 16),
            children: [_stack([side.first, details, ...side.skip(1)])],
          );

    return Column(
      children: [
        // The header carries the actions, so the whole height below is form.
        _TopBar(type: _type, item: _item, actions: _actions()),
        Expanded(child: body),
      ],
    );
  }

  Widget _stack(List<Widget> children) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [for (var i = 0; i < children.length; i++) ...[if (i > 0) const SizedBox(height: 14), children[i]]],
      );

  Widget _actions() {
    if (!_canSave) return const SizedBox.shrink();
    return BlocSelector<CatalogFormCubit, CatalogFormState, bool>(
      selector: (s) => s.busy,
      builder: (context, busy) => _ActionButtons(
        busy: busy,
        isNew: _item.isNew,
        onSave: () => _save(),
        onSaveAnother: () => _save(another: true),
        onDelete: _canDelete ? _delete : null,
      ),
    );
  }

  Widget _preview() => ListenableBuilder(
        listenable: Listenable.merge([_name, _mrp, _time]),
        builder: (context, _) => LivePreviewCard(
          type: _type,
          name: _name.text.trim(),
          subtitle: [
            _mainCategory?.name ?? 'Category',
            _type.isProduct ? (_unit?.name ?? 'Unit') : '${_time.text.trim().isEmpty ? 0 : _time.text.trim()} min',
          ].join(' · '),
          price: Money.plain(_parse(_mrp)),
          badge: !_active ? 'Disabled' : (_type.isProduct ? (_selling ? 'Selling' : 'Not selling') : 'Active'),
          thumbnail: _item.thumbnail,
        ),
      );

  Widget _text(String label, TextEditingController c,
          {String? hint, String? error, bool required = false, bool numeric = false, bool decimal = true, bool rtl = false, int lines = 1}) =>
      LabeledField(
        label: label,
        required: required,
        error: error,
        child: InputBox(controller: c, hint: hint, numeric: numeric, decimal: decimal, rtl: rtl, lines: lines, hasError: error != null),
      );

  Widget _picker(String label, CatalogRef? value, String placeholder, VoidCallback onTap, {String? error, bool required = false}) =>
      LabeledField(
        label: label,
        required: required,
        error: error,
        child: PickerBox(value: value?.name, placeholder: placeholder, onTap: onTap, hasError: error != null),
      );

  Widget _details(CatalogOptions options) {
    final subs = _mainCategory?.id == null ? const <CatalogOption>[] : options.subCategoriesOf(_mainCategory!.id);
    return FormSectionCard(
      icon: _type.isProduct ? Icons.sell_outlined : Icons.content_cut,
      title: '${_type.label} Details',
      trailing: _type.isProduct
          ? GestureDetector(
              onTap: () => setState(() => _selling = !_selling),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Text('Selling', style: ui(size: 12, weight: FontWeight.w700, color: context.astra.textSecondary)),
                const SizedBox(width: 8),
                PillSwitch(value: _selling),
              ]),
            )
          : null,
      children: [
        _text('${_type.label} Name', _name, hint: 'Enter ${_type.label.toLowerCase()} name', required: true, error: _errors['name']),
        _text('Arabic Name', _nameArabic, hint: 'الاسم بالعربية', rtl: true),
        FieldPair(
          _text('Code', _code, hint: _item.isNew ? 'Generated if empty' : null),
          LabeledField(
            label: 'Status',
            child: SegmentedChoice<bool>(
              height: 44,
              options: const [(true, 'Active', null), (false, 'Disabled', null)],
              selected: _active,
              onChanged: (v) => setState(() => _active = v),
            ),
          ),
        ),
        _picker('Department', _department, 'Select department',
            () => _pick('Department', options.departments, _department, (r) => _department = r),
            required: true, error: _errors['department']),
        FieldPair(
          _picker('Main Category', _mainCategory, 'Select', () => _pick('Main category', options.mainCategories, _mainCategory, (r) {
                if (r != _mainCategory) _subCategory = null;
                _mainCategory = r;
              }), required: true, error: _errors['mainCategory']),
          _picker('Sub Category', _subCategory, 'Select',
              () => _pick('Sub category', subs, _subCategory, (r) => _subCategory = r, optional: true)),
        ),
        if (_type.isProduct) ...[
          FieldPair(
            _picker('Unit', _unit, 'Select', () => _pick('Unit', options.units, _unit, (r) => _unit = r, create: false),
                required: true, error: _errors['unit']),
            _text('Priority', _priority, hint: '0', numeric: true, decimal: false),
          ),
          FieldPair(
            _picker('Brand', _brand, 'Select brand', () => _pick('Brand', options.brands, _brand, (r) => _brand = r, optional: true)),
            _text('Size', _size, hint: 'Size'),
          ),
          _text('Size Category', _sizeCategory, hint: 'Size category name', rtl: true),
          FieldPair(_text('HSN Code', _hsn, hint: 'HSN code'), _text('Tax %', _tax, hint: '0', numeric: true)),
          if (options.barcodeTypedPerProduct) _text('Barcode', _barcode, hint: 'Scan or type a unique barcode'),
        ] else
          _text('Priority', _priority, hint: '0', numeric: true, decimal: false),
        _text('Description', _description, hint: 'Enter ${_type.label.toLowerCase()} description', lines: 3),
        ToggleRow(
          icon: Icons.favorite_border,
          title: 'Favourite',
          subtitle: 'Pinned to the top of the POS catalog',
          value: _favorite,
          onChanged: (v) => setState(() => _favorite = v),
        ),
      ],
    );
  }

  List<Widget> _pricing() {
    if (!_type.isProduct) {
      return [
        FormSectionCard(icon: Icons.payments_outlined, title: 'Service Pricing', children: [
          _text('Price', _mrp, hint: '0.00', numeric: true, required: true, error: _errors['mrp']),
        ]),
        FormSectionCard(icon: Icons.schedule, title: 'Service Duration', children: [
          _text('Duration (minutes)', _time, hint: 'e.g. 45', numeric: true),
          ListenableBuilder(
            listenable: _time,
            builder: (context, _) => Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (final m in const [15, 30, 45, 60, 90, 120, 150, 180])
                  ActionChipButton(
                    label: m < 60 ? '${m}m' : '${m ~/ 60}h${m % 60 == 0 ? '' : ' ${m % 60}m'}',
                    active: _parse(_time) == m,
                    onTap: () => _time.text = '$m',
                  ),
              ],
            ),
          ),
        ]),
      ];
    }
    return [
      ListenableBuilder(
        listenable: Listenable.merge([_cost, _tax]),
        builder: (context, _) {
          final b = PriceBreakdown(cost: _parse(_cost), mrp: _parse(_mrp), taxPercent: _parse(_tax));
          return FormSectionCard(icon: Icons.shopping_cart_outlined, title: 'Purchase Information', children: [
            _text('Cost', _cost, hint: '0.00', numeric: true, required: true, error: _errors['cost']),
            CalcStrip(items: [
              (label: 'Without tax', value: Money.plain(b.costWithoutTax), good: false),
              (label: 'Tax amount', value: Money.plain(b.costTax), good: false),
              (label: 'Cost', value: Money.plain(_parse(_cost)), good: false),
            ]),
          ]);
        },
      ),
      ListenableBuilder(
        listenable: Listenable.merge([_cost, _mrp, _tax]),
        builder: (context, _) {
          final b = PriceBreakdown(cost: _parse(_cost), mrp: _parse(_mrp), taxPercent: _parse(_tax));
          return FormSectionCard(
            icon: Icons.point_of_sale_outlined,
            title: 'Sales Information',
            trailing: Text('${b.marginPercent.toStringAsFixed(0)}% margin',
                style: ui(size: 12, weight: FontWeight.w700, color: b.profit >= 0 ? AstraPalette.success : AstraPalette.danger)),
            children: [
              _text('MRP (selling price)', _mrp, hint: '0.00', numeric: true, required: true, error: _errors['mrp']),
              CalcStrip(items: [
                (label: 'Without tax', value: Money.plain(b.mrpWithoutTax), good: false),
                (label: 'Profit', value: Money.plain(b.profit), good: b.profit > 0),
                (label: 'Tax amount', value: Money.plain(b.mrpTax), good: false),
              ]),
            ],
          );
        },
      ),
    ];
  }

  Widget _photos() => BlocBuilder<CatalogFormCubit, CatalogFormState>(
        buildWhen: (a, b) => a.item?.images != b.item?.images || a.pendingPhotos != b.pendingPhotos,
        builder: (context, s) {
          final cubit = context.read<CatalogFormCubit>();
          final images = s.item?.images ?? const <CatalogImage>[];
          final count = images.length + s.pendingPhotos.length;
          return FormSectionCard(
            icon: Icons.photo_library_outlined,
            title: _type.isProduct ? 'Product Images' : 'Images',
            trailing: count == 0 ? null : Text('$count photo${count == 1 ? '' : 's'}', style: ui(size: 12, weight: FontWeight.w700, color: context.astra.textMuted)),
            children: [
              PhotoGrid(
                type: _type,
                images: images,
                pending: s.pendingPhotos,
                canEdit: _canSave,
                onAdd: _addPhoto,
                onRemove: cubit.removePhoto,
                onRemovePending: cubit.removePendingPhoto,
              ),
              if (_item.isNew && s.pendingPhotos.isNotEmpty)
                Text('Photos upload when you save.', style: ui(size: 11, weight: FontWeight.w500, color: context.astra.textMuted)),
            ],
          );
        },
      );
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.type, required this.item, required this.actions});

  final CatalogType type;
  final CatalogItem item;
  final Widget actions;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tablet = context.isTablet;
    final top = tablet ? 10.0 : MediaQuery.paddingOf(context).top + 4;
    return Container(
      padding: EdgeInsets.fromLTRB(4, top, tablet ? 22 : 12, 10),
      decoration: BoxDecoration(color: p.canvas, border: Border(bottom: BorderSide(color: p.hairline))),
      child: Row(
        children: [
          IconButton(
            icon: Icon(Icons.arrow_back, color: p.ink),
            onPressed: () => CatalogFormScreen.close(context, changed: context.read<CatalogFormCubit>().savedAny),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${item.isNew ? 'New' : 'Edit'} ${type.label.toLowerCase()}',
                    maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 17, weight: FontWeight.w800, color: p.ink)),
                Text(
                  item.isNew ? 'Settings › ${type.plural} · not saved yet' : 'Settings › ${type.plural} · #${item.id} · ${item.code}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          actions,
        ],
      ),
    );
  }
}

/// Delete (existing item), Save & New (new item) and Save, sized for the
/// header: labelled on a tablet, Save & New as an icon on a phone.
class _ActionButtons extends StatelessWidget {
  const _ActionButtons({required this.busy, required this.isNew, required this.onSave, required this.onSaveAnother, this.onDelete});

  final bool busy;
  final bool isNew;
  final VoidCallback onSave;
  final VoidCallback onSaveAnother;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final wide = context.isTablet;
    final shape = RoundedRectangleBorder(borderRadius: BorderRadius.circular(12));
    final outline = OutlinedButton.styleFrom(
      shape: shape,
      backgroundColor: p.cardSolid,
      side: BorderSide(color: p.hairline),
      minimumSize: const Size(42, 40),
      padding: EdgeInsets.symmetric(horizontal: wide ? 14 : 0),
    );
    Widget gap() => const SizedBox(width: 8);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (onDelete != null) ...[
          Tooltip(
            message: 'Delete',
            child: OutlinedButton(
              style: outline.copyWith(padding: const WidgetStatePropertyAll(EdgeInsets.zero)),
              onPressed: busy ? null : onDelete,
              child: const Icon(Icons.delete_outline, size: 20, color: AstraPalette.danger),
            ),
          ),
          gap(),
        ],
        if (isNew) ...[
          Tooltip(
            message: 'Save & New',
            child: OutlinedButton(
              style: outline,
              onPressed: busy ? null : onSaveAnother,
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.library_add_outlined, size: 18, color: p.ink),
                if (wide) ...[const SizedBox(width: 6), Text('Save & New', style: ui(size: 13, weight: FontWeight.w800, color: p.ink))],
              ]),
            ),
          ),
          gap(),
        ],
        FilledButton.icon(
          style: FilledButton.styleFrom(
            shape: shape,
            backgroundColor: p.primary,
            minimumSize: const Size(0, 40),
            padding: EdgeInsets.symmetric(horizontal: wide ? 18 : 14),
          ),
          onPressed: busy ? null : onSave,
          icon: busy
              ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Icon(Icons.check, size: 18, color: Colors.white),
          label: Text(wide && !isNew ? 'Save changes' : 'Save', style: ui(size: 13.5, weight: FontWeight.w800, color: Colors.white)),
        ),
      ],
    );
  }
}
