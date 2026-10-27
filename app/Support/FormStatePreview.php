<?php

namespace App\Support;

use Filament\Forms\Get;
use Filament\Forms\Set;

// フォームの今の入力を写した配列の上で、Get / Set を使う処理（一括入力の反映など）を試しに動かす。
// 本物のフォームは変えないので、「反映したらどうなるか」を先に見て、確認のダイアログを出せる
class FormStatePreview
{
    public array $root;

    public function __construct(array $data)
    {
        $this->root = ['data' => $data];
    }

    public function get(): Get
    {
        $preview = $this;

        return new class($preview) extends Get {
            public function __construct(private FormStatePreview $preview) {}

            public function __invoke($path = '', bool $isAbsolute = false): mixed
            {
                return data_get($this->preview->root, $this->preview->path($path, $isAbsolute));
            }
        };
    }

    public function set(): Set
    {
        $preview = $this;

        return new class($preview) extends Set {
            public function __construct(private FormStatePreview $preview) {}

            public function __invoke($path, mixed $state, bool $isAbsolute = false): mixed
            {
                data_set($this->preview->root, $this->preview->path($path, $isAbsolute), $state);

                return $state;
            }
        };
    }

    // 一括入力の欄はフォームのいちばん外側（data の直下）にあるので、相対のパスは data. を付ける
    public function path($path, bool $isAbsolute): string
    {
        $path = (string) $path;

        return $isAbsolute ? $path : 'data' . ($path === '' ? '' : '.' . $path);
    }

    public function data(): array
    {
        return $this->root['data'];
    }
}
