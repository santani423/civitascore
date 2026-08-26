/// Padanan `QuestionSelectionMode` di backend (Modules\Academic\Enums).
enum QuestionSelectionMode {
  all('all', 'Semua Soal'),
  random('random', 'Acak dari Question Pool'),
  manual('manual', 'Pilih Soal Secara Manual');

  const QuestionSelectionMode(this.apiValue, this.label);

  final String apiValue;
  final String label;

  static QuestionSelectionMode fromApiValue(String value) =>
      QuestionSelectionMode.values.firstWhere((e) => e.apiValue == value);
}
