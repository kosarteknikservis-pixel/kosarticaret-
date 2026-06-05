<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteBackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SiteBackupController extends Controller
{
    public function index(SiteBackupManager $backups): View
    {
        return view('admin.site-backups.index', [
            'backups' => $backups->backups(),
            'backupDirectory' => $backups->backupDirectory(),
        ]);
    }

    public function store(Request $request, SiteBackupManager $backups): RedirectResponse
    {
        $data = $request->validate([
            'backup_name' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $path = $backups->create($data['backup_name'] ?? null);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.site-backups.index')->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.site-backups.index')
            ->with('success', basename($path).' oluşturuldu.');
    }

    public function upload(Request $request, SiteBackupManager $backups): RedirectResponse
    {
        $data = $request->validate([
            'backup_file' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ]);

        try {
            $path = $backups->import($data['backup_file']);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.site-backups.index')->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.site-backups.index')
            ->with('success', basename($path).' yüklendi. Geri yüklemeden önce doğru yedeği seçtiğinizden emin olun.');
    }

    public function download(Request $request, SiteBackupManager $backups): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
        ]);

        try {
            $path = $backups->pathFor($data['file']);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.site-backups.index')->with('error', $exception->getMessage());
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function restore(Request $request, SiteBackupManager $backups): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'confirm_restore' => ['accepted'],
        ]);

        try {
            $backups->restore($data['file']);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.site-backups.index')->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.site-backups.index')
            ->with('success', 'Site yedeği geri yüklendi. Geri yükleme öncesi mevcut site otomatik yedeklendi.');
    }

    public function destroy(Request $request, SiteBackupManager $backups): RedirectResponse|Response
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
        ]);

        try {
            $deleted = $backups->delete($data['file']);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.site-backups.index')->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.site-backups.index')
            ->with($deleted ? 'success' : 'error', $deleted ? 'Yedek silindi.' : 'Yedek silinemedi.');
    }
}
