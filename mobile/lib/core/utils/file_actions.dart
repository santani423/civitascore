import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:open_filex/open_filex.dart';

import '../l10n/core_strings.dart';
import '../network/api_client.dart';
import '../widgets/app_feedback.dart';

/// Allowed by `POST /file-uploads` (FileManagement module).
const kUploadExtensions = [
  'pdf',
  'jpg',
  'jpeg',
  'png',
  'doc',
  'docx',
  'xls',
  'xlsx',
];
const kDefaultMaxUploadMb = 10;

class PickedUpload {
  const PickedUpload({
    required this.path,
    required this.name,
    required this.size,
  });
  final String path;
  final String name;
  final int size;
}

/// Downloads an authenticated document (PDF) and opens it with the system
/// viewer, showing progress/errors as snackbars.
Future<void> downloadAndOpen(
  BuildContext context,
  WidgetRef ref, {
  required String path,
  required String filename,
  Map<String, dynamic>? query,
}) async {
  final s = context.l10n;
  showAppSnack(context, s.downloading);
  try {
    final file = await ref
        .read(apiClientProvider)
        .download(path, filename: filename, query: query);
    final result = await OpenFilex.open(file.path);
    if (result.type != ResultType.done && context.mounted) {
      showAppSnack(context, s.openFileFailed, isError: true);
    }
  } catch (e) {
    if (context.mounted) showAppSnack(context, s.errorFor(e), isError: true);
  }
}

/// Lets the user pick a document or take a photo; enforces extension and
/// size limits before upload.
Future<PickedUpload?> pickUpload(
  BuildContext context, {
  List<String> extensions = kUploadExtensions,
  int maxMb = kDefaultMaxUploadMb,
  bool allowCamera = true,
}) async {
  final s = context.l10n;
  String? source = 'file';
  if (allowCamera) {
    source = await showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.attach_file),
              title: Text(s.chooseFile),
              onTap: () => Navigator.pop(ctx, 'file'),
            ),
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(s.takePhoto),
              onTap: () => Navigator.pop(ctx, 'camera'),
            ),
          ],
        ),
      ),
    );
  }
  if (source == null) return null;

  PickedUpload? picked;
  if (source == 'camera') {
    final photo = await ImagePicker().pickImage(
      source: ImageSource.camera,
      imageQuality: 80,
      maxWidth: 2000,
    );
    if (photo != null) {
      picked = PickedUpload(
        path: photo.path,
        name: photo.name,
        size: await File(photo.path).length(),
      );
    }
  } else {
    final f = await FilePicker.pickFile(
      type: FileType.custom,
      allowedExtensions: extensions,
    );
    final path = f?.path;
    if (f != null && path != null) {
      picked = PickedUpload(
        path: path,
        name: f.name,
        size: await f.xFile.length(),
      );
    }
  }
  if (picked == null) return null;
  if (picked.size > maxMb * 1024 * 1024) {
    if (context.mounted) {
      showAppSnack(context, s.fileTooLarge(maxMb), isError: true);
    }
    return null;
  }
  return picked;
}
