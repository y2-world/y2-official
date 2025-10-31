<?php

namespace App\Http\Traits;

trait HasInfiniteScroll
{
    /**
     * AJAX/JSONリクエストに対応したページネーションレスポンスを返す
     *
     * @param mixed $paginator Paginatorインスタンス
     * @param string $partialView パーシャルビューの名前
     * @param array $data ビューに渡すデータ
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function handleInfiniteScrollRequest($paginator, string $partialView, array $data = [])
    {
        if (request()->wantsJson() || request()->ajax()) {
            $html = view($partialView, array_merge($data, ['items' => $paginator]))->render();

            return response()->json([
                'html' => $html,
                'next_page_url' => $paginator->nextPageUrl(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]);
        }

        return null;
    }
}
