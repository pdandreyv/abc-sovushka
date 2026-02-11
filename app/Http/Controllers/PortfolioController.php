<?php

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use App\Models\SubscriptionOrder;
use App\Models\User;
use App\Services\CertificateImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioController extends Controller
{
    /**
     * Страница «Портфолио» в ЛК: сертификаты, дипломы, награды.
     * Именной сертификат (user_id заполнен): показывается только этому пользователю.
     * Не именной: показывается, если у пользователя была подписка на указанный уровень в период [date_from, date_to].
     */
    public function index()
    {
        $allItems = PortfolioItem::where('display', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $userId = Auth::id();
        $items = $allItems->filter(function (PortfolioItem $item) use ($userId) {
            if ($item->user_id !== null) {
                return (int) $item->user_id === (int) $userId;
            }
            if ($item->subscription_level_id === null || $item->date_from === null || $item->date_to === null) {
                return false;
            }
            return $this->userHadSubscriptionInPeriod(
                $userId,
                (int) $item->subscription_level_id,
                $item->date_from->toDateString(),
                $item->date_to->toDateString()
            );
        })->values();

        return view('portfolio.index', [
            'items' => $items,
        ]);
    }

    /**
     * Была ли у пользователя оплаченная подписка на уровень в указанный период (пересечение периодов).
     */
    private function userHadSubscriptionInPeriod(?int $userId, int $levelId, string $dateFrom, string $dateTo): bool
    {
        if (!$userId) {
            return false;
        }

        $levelStr = (string) $levelId;

        return SubscriptionOrder::query()
            ->where('user_id', $userId)
            ->where('paid', true)
            ->whereDate('date_subscription', '<=', $dateTo)
            ->whereDate('date_till', '>=', $dateFrom)
            ->where(function ($q) use ($levelStr) {
                $q->where('subscription_level_ids', $levelStr)
                    ->orWhere('subscription_level_ids', 'like', $levelStr . ',%')
                    ->orWhere('subscription_level_ids', 'like', '%,' . $levelStr . ',%')
                    ->orWhere('subscription_level_ids', 'like', '%,' . $levelStr)
                    ->orWhere('levels', $levelStr)
                    ->orWhere('levels', 'like', $levelStr . ',%')
                    ->orWhere('levels', 'like', '%,' . $levelStr . ',%')
                    ->orWhere('levels', 'like', '%,' . $levelStr);
            })
            ->exists();
    }

    /**
     * Выдача файла сертификата: просмотр или скачивание.
     * Для не именного сертификата на изображение накладывается ФИО пользователя (или email/phone/user_code).
     * Исходный файл не изменяется.
     */
    public function certificateFile(Request $request, int $id)
    {
        $item = PortfolioItem::where('id', $id)->where('display', true)->firstOrFail();
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        if (!$this->userCanAccessItem($item, $user->id)) {
            Log::channel('single')->warning('PortfolioController: certificateFile 404 no access', ['item_id' => $id, 'user_id' => $user->id]);
            abort(404);
        }

        if (!$item->image_file) {
            Log::channel('single')->warning('PortfolioController: certificateFile 404 no image_file', ['item_id' => $id]);
            abort(404);
        }

        $path = public_path('files/portfolio_items/' . $item->id . '/image_file/' . $item->image_file);
        Log::channel('single')->info('PortfolioController: certificateFile path check', [
            'path' => $path,
            'is_file' => is_file($path),
            'item_id' => $item->id,
            'image_file' => $item->image_file,
        ]);
        if (!is_file($path)) {
            Log::channel('single')->warning('PortfolioController: certificateFile 404 file not found', ['path' => $path]);
            abort(404);
        }

        $download = $request->boolean('download');
        $filename = pathinfo($item->image_file, PATHINFO_BASENAME);

        if ($item->user_id !== null) {
            return response()->file($path, [
                'Content-Type' => $this->mimeForExt(pathinfo($path, PATHINFO_EXTENSION)),
                'Content-Disposition' => $download ? 'attachment; filename="' . $filename . '"' : 'inline',
            ]);
        }

        Log::channel('single')->info('PortfolioController: certificateFile non-personal', [
            'item_id' => $item->id,
            'path' => $path,
            'user_id' => $user->id,
        ]);

        $service = app(CertificateImageService::class);
        try {
            $tempPath = $service->createWithText($path, $user);
        } catch (\Throwable $e) {
            Log::channel('single')->error('PortfolioController: createWithText exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        $existsBeforeResponse = is_file($tempPath);
        Log::channel('single')->info('PortfolioController: temp file before response', [
            'tempPath' => $tempPath,
            'exists' => $existsBeforeResponse,
            'size' => $existsBeforeResponse ? filesize($tempPath) : 0,
        ]);

        $mime = $this->mimeForExt(pathinfo($tempPath, PATHINFO_EXTENSION));
        $disposition = $download ? 'attachment; filename="' . $filename . '"' : 'inline';
        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
        ];
        $size = @filesize($tempPath);
        if ($size !== false) {
            $headers['Content-Length'] = $size;
        }

        return new StreamedResponse(function () use ($tempPath) {
            $exists = is_file($tempPath);
            Log::channel('single')->info('PortfolioController: stream callback start', ['tempPath' => $tempPath, 'exists' => $exists]);
            $handle = $exists ? fopen($tempPath, 'rb') : false;
            if ($handle) {
                $bytes = 0;
                while (!feof($handle)) {
                    $chunk = fread($handle, 65536);
                    echo $chunk;
                    $bytes += strlen($chunk);
                    flush();
                }
                fclose($handle);
                Log::channel('single')->info('PortfolioController: stream callback sent bytes', ['bytes' => $bytes]);
            }
            $unlinked = @unlink($tempPath);
            Log::channel('single')->info('PortfolioController: stream callback unlink', ['tempPath' => $tempPath, 'unlinked' => $unlinked]);
        }, 200, $headers);
    }

    private function userCanAccessItem(PortfolioItem $item, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        if ($item->user_id !== null) {
            return (int) $item->user_id === (int) $userId;
        }
        if ($item->subscription_level_id === null || $item->date_from === null || $item->date_to === null) {
            return false;
        }
        return $this->userHadSubscriptionInPeriod(
            $userId,
            (int) $item->subscription_level_id,
            $item->date_from->toDateString(),
            $item->date_to->toDateString()
        );
    }

    private function mimeForExt(string $ext): string
    {
        return match (strtolower($ext)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}
