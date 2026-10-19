/// Padanan `QuestionSelectionMode` di backend (Modules\Academic\Enums).
/// Label terlokalisasi ada di `ExamStrings.selectionModeLabel`.
enum QuestionSelectionMode {
  all('all'),
  random('random'),
  manual('manual');

  const QuestionSelectionMode(this.apiValue);

  final String apiValue;

  static QuestionSelectionMode fromApiValue(String? value) =>
      QuestionSelectionMode.values.firstWhere(
        (e) => e.apiValue == value,
        orElse: () => QuestionSelectionMode.all,
      );
}
