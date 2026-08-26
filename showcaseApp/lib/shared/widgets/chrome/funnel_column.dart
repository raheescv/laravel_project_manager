import 'package:flutter/material.dart';

import '../../../features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';

/// The choices made so far, pinned to the left of every funnel screen on tablet.
///
/// This is what stops the funnel being a corridor: a completed step shows its
/// answer and reopens on tap, the current step is an ink marker, later steps are
/// dimmed. Nothing here is destructive — reopening a step keeps everything
/// before it.
class FunnelColumn extends StatelessWidget {
  const FunnelColumn({
    super.key,
    required this.state,
    required this.current,
    required this.onReopen,
  });

  final FunnelState state;
  final FunnelStep current;
  final void Function(FunnelStep) onReopen;

  @override
  Widget build(BuildContext context) {
    final steps = <_StepData>[
      _StepData(FunnelStep.category, 'Category', state.category?.name ?? '—'),
      _StepData(
        FunnelStep.size,
        'Size',
        state.size ??
            // "Any size" is an answer, not a prompt — it must not appear while
            // the step is still the one being answered.
            (current == FunnelStep.size
                ? 'Choosing…'
                : state.category == null
                    ? '—'
                    : 'Any size'),
      ),
      _StepData(
        FunnelStep.brand,
        'Brand',
        state.brand?.name ??
            (current == FunnelStep.brand ? 'Choosing…' : 'Any brand'),
      ),
      _StepData(
        FunnelStep.results,
        'Results',
        state.brand != null ? '${state.brand!.productCount} products' : '—',
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (var i = 0; i < steps.length; i++)
          _StepRow(
            index: i,
            data: steps[i],
            done: steps[i].step.index < current.index,
            active: steps[i].step == current,
            onTap: steps[i].step.index < current.index
                ? () => onReopen(steps[i].step)
                : null,
          ),
      ],
    );
  }
}

class _StepData {
  const _StepData(this.step, this.label, this.value);

  final FunnelStep step;
  final String label;
  final String value;
}

class _StepRow extends StatelessWidget {
  const _StepRow({
    required this.index,
    required this.data,
    required this.done,
    required this.active,
    this.onTap,
  });

  final int index;
  final _StepData data;
  final bool done;
  final bool active;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final future = !done && !active;
    return Opacity(
      opacity: future ? .45 : 1,
      child: InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 13),
          decoration: BoxDecoration(
            color: active ? p.surface : null,
            border: Border.all(color: active ? p.line : Colors.transparent),
          ),
          child: Row(
            children: [
              Container(
                width: 22,
                height: 22,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: done ? p.accent : null,
                  border: Border.all(color: done ? p.accent : p.line),
                ),
                child: done
                    ? Icon(Icons.check, size: 12, color: p.accentInk)
                    : Text(
                        '${index + 1}',
                        style: PearlText.micro.copyWith(
                          fontSize: 9,
                          letterSpacing: 0,
                          color: active ? p.ink : p.faint,
                        ),
                      ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      data.label.toUpperCase(),
                      style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      data.value,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: PearlText.label.copyWith(color: p.ink, fontSize: 12.5),
                    ),
                  ],
                ),
              ),
              if (done) Icon(Icons.chevron_right, size: 15, color: p.faint),
            ],
          ),
        ),
      ),
    );
  }
}

/// The phone equivalent: the same choices as a tappable breadcrumb strip.
class FunnelBreadcrumbs extends StatelessWidget {
  const FunnelBreadcrumbs({
    super.key,
    required this.state,
    required this.current,
    required this.onReopen,
    this.trailing,
  });

  final FunnelState state;
  final FunnelStep current;
  final void Function(FunnelStep) onReopen;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final crumbs = <(FunnelStep, String)>[
      if (state.category != null) (FunnelStep.category, state.category!.name),
      if (state.size != null) (FunnelStep.size, state.size!),
      if (state.brand != null) (FunnelStep.brand, state.brand!.name),
    ];

    return SizedBox(
      height: 34,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: PearlMetrics.pad),
        itemCount: crumbs.length + (trailing != null ? 1 : 0),
        itemBuilder: (context, i) {
          final last = i == crumbs.length;
          if (last) {
            return Center(
              child: Text(
                trailing!.toUpperCase(),
                style: PearlText.micro.copyWith(color: p.ink),
              ),
            );
          }
          final crumb = crumbs[i];
          return Row(
            children: [
              InkWell(
                onTap: () => onReopen(crumb.$1),
                child: Center(
                  child: Text(
                    crumb.$2.toUpperCase(),
                    style: PearlText.micro.copyWith(color: p.muted),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 9),
                child: Icon(Icons.chevron_right, size: 13, color: p.faint),
              ),
            ],
          );
        },
      ),
    );
  }
}
