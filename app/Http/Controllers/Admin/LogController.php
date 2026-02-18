<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    private const TAIL_BYTES = 500 * 1024;

    public function index(): View
    {
        $files = collect(File::files($this->logsPath()))
            ->filter(fn (\SplFileInfo $file): bool => $file->isFile())
            ->map(function (\SplFileInfo $file): array {
                $size = $file->getSize();

                return [
                    'name' => $file->getFilename(),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            })
            ->sortByDesc('modified_at')
            ->values();

        return view('admin.logs.index', [
            'files' => $files,
        ]);
    }

    public function download(string $file): BinaryFileResponse
    {
        $path = $this->resolveLogPath($file);

        return response()->download($path, $file);
    }

    public function view(string $file): View
    {
        $path = $this->resolveLogPath($file);
        $size = File::size($path);
        $tail = $this->readTail($path, self::TAIL_BYTES);

        return view('admin.logs.show', [
            'fileName' => $file,
            'content' => $tail['content'],
            'truncated' => $tail['truncated'],
            'tailBytes' => self::TAIL_BYTES,
            'size' => $size,
            'sizeHuman' => $this->formatBytes($size),
        ]);
    }

    private function logsPath(): string
    {
        return storage_path('logs');
    }

    private function resolveLogPath(string $file): string
    {
        if (basename($file) !== $file) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $path = $this->logsPath().DIRECTORY_SEPARATOR.$file;

        if (! File::exists($path) || ! File::isFile($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $path;
    }

    /**
     * @return array{content:string,truncated:bool}
     */
    private function readTail(string $path, int $limit): array
    {
        $size = File::size($path);
        $offset = max(0, $size - $limit);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Não foi possível abrir o arquivo de log.');
        }

        fseek($handle, $offset);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return [
            'content' => $content,
            'truncated' => $offset > 0,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2, ',', '.').' '.$units[$power];
    }
}
