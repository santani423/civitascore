import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/domain/auth_user.dart';
import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../storage/local_prefs.dart';
import '../storage/storage_keys.dart';

/// Which shell the user sees. Lecturers and pure academic advisors share the
/// staff shell; tabs inside it are filtered by permission.
enum AppExperience { student, lecturer }

List<AppExperience> availableExperiences(AuthUser user) => [
  if (user.isStudent) AppExperience.student,
  if (user.isLecturer || user.isAdvisor) AppExperience.lecturer,
];

class ActiveExperienceController extends Notifier<AppExperience?> {
  @override
  AppExperience? build() {
    final user = ref.watch(currentUserProvider);
    if (user == null) return null;
    final available = availableExperiences(user);
    if (available.isEmpty) return null;
    final saved = ref
        .read(sharedPreferencesProvider)
        .getString(StorageKeys.activeExperience);
    return available.firstWhere(
      (e) => e.name == saved,
      orElse: () => available.first,
    );
  }

  Future<void> switchTo(AppExperience experience) async {
    state = experience;
    await ref
        .read(sharedPreferencesProvider)
        .setString(StorageKeys.activeExperience, experience.name);
  }
}

final activeExperienceProvider =
    NotifierProvider<ActiveExperienceController, AppExperience?>(
      ActiveExperienceController.new,
    );
