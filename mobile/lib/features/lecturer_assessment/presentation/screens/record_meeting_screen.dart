import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/assessment_api.dart';
import '../../domain/assessment_models.dart';
import '../../l10n/assessment_strings.dart';
import '../providers.dart';
import '../widgets/status_helpers.dart';

/// Form that records (or corrects, when [meeting] is given or the number
/// already exists) one meeting for the whole roster.
class RecordMeetingScreen extends ConsumerStatefulWidget {
  const RecordMeetingScreen({
    super.key,
    required this.classSectionId,
    required this.sheet,
    this.meeting,
  });
  final String classSectionId;
  final AttendanceSheet sheet;
  final Meeting? meeting;

  @override
  ConsumerState<RecordMeetingScreen> createState() => _State();
}

class _State extends ConsumerState<RecordMeetingScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _number;
  late DateTime _date;
  late Map<String, AttendanceStatus> _status;
  bool _busy = false;
  Map<String, String> _serverErrors = {};

  @override
  void initState() {
    super.initState();
    final m = widget.meeting;
    _number = TextEditingController(
      text: '${m?.number ?? widget.sheet.nextMeetingNumber}',
    );
    _date = m?.date ?? DateTime.now();
    _status = {
      for (final st in widget.sheet.roster)
        st.krsItemId: m?.statusOf(st.krsItemId) ?? AttendanceStatus.present,
    };
    _number.addListener(_onNumber);
  }

  @override
  void dispose() {
    _number.dispose();
    super.dispose();
  }

  void _onNumber() => setState(() {});

  bool get _overwrites {
    final n = int.tryParse(_number.text.trim());
    return n != null && widget.sheet.meetings.any((m) => m.number == n);
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _serverErrors = {};
    });
    try {
      final message = await ref
          .read(assessmentApiProvider)
          .recordMeeting(
            widget.classSectionId,
            meetingNumber: int.parse(_number.text.trim()),
            meetingDate: _date,
            entries: _status,
          );
      ref.invalidate(attendanceSheetProvider(widget.classSectionId));
      if (!mounted) return;
      showAppSnack(context, message ?? context.l10n.savedSuccessfully);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        if (e is ApiFailure) {
          _serverErrors = {
            for (final k in e.fieldErrors.keys) k: e.fieldError(k) ?? '',
          };
        }
      });
      showAppSnack(context, context.assess.errorText(e), isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final roster = widget.sheet.roster;
    final editing = widget.meeting != null;
    return Scaffold(
      appBar: AppBar(title: Text(editing ? s.editMeeting : s.recordMeeting)),
      body: CenteredBody(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _number,
                      readOnly: editing,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: s.meetingNumber,
                        errorText: _serverErrors['meeting_number'],
                      ),
                      validator: (v) {
                        final req = Validators.required(context.l10n)(v);
                        if (req != null) return req;
                        if (int.tryParse(v!.trim()) == null) {
                          return s.integerRequired;
                        }
                        return Validators.numberInRange(context.l10n, 1, 255)(
                          v,
                        );
                      },
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: InkWell(
                      onTap: _pickDate,
                      child: InputDecorator(
                        decoration: InputDecoration(
                          labelText: s.meetingDate,
                          errorText: _serverErrors['meeting_date'],
                          suffixIcon: const Icon(Icons.calendar_today),
                        ),
                        child: Text(context.formatDate(_date)),
                      ),
                    ),
                  ),
                ],
              ),
              if (_overwrites && !editing) ...[
                const SizedBox(height: AppSpacing.md),
                AppBanner(message: s.overwriteNotice, tone: StatusTone.warning),
              ],
              const SizedBox(height: AppSpacing.lg),
              Text(s.markAll, style: Theme.of(context).textTheme.labelLarge),
              const SizedBox(height: AppSpacing.xs),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  for (final st in AttendanceStatus.values)
                    ActionChip(
                      label: Text(s.statusLabel(st)),
                      onPressed: () => setState(() {
                        for (final k in _status.keys.toList()) {
                          _status[k] = st;
                        }
                      }),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),
              Text(
                '${s.students} (${roster.length})',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              if (roster.isEmpty)
                Padding(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: Text(s.noStudentsBody),
                ),
              for (final st in roster)
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm),
                  child: AppSurfaceCard(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          st.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context).textTheme.titleSmall,
                        ),
                        Text(
                          st.nim,
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                        const SizedBox(height: AppSpacing.xs),
                        Wrap(
                          spacing: AppSpacing.sm,
                          children: [
                            for (final v in AttendanceStatus.values)
                              ChoiceChip(
                                key: ValueKey('${st.krsItemId}:${v.value}'),
                                label: Text(s.statusLabel(v)),
                                selected: _status[st.krsItemId] == v,
                                onSelected: (_) =>
                                    setState(() => _status[st.krsItemId] = v),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              if (_serverErrors['entries'] != null)
                Text(
                  _serverErrors['entries']!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              const SizedBox(height: AppSpacing.xl),
              FilledButton.icon(
                onPressed: _busy || roster.isEmpty ? null : _submit,
                icon: _busy
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.save_outlined),
                label: Text(s.saveAttendance),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
