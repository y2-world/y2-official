# Yuki Yoshida Official Website

Yuki Yoshidaの公式ウェブサイト。ニュース、ディスコグラフィー、セットリスト、プロフィール情報を掲載。

## 機能

- **ニュース管理**: リッチテキストと画像付きのニュース記事の投稿・管理
- **音楽ディスコグラフィー**: アルバムとシングルの収録曲情報を表示
- **セットリストデータベース**: ライブパフォーマンスとセットリストの包括的なデータベース
- **プロフィール**: アーティストの経歴とプロフィール情報
- **管理画面**: Filamentを使用した簡単なコンテンツ管理

## 技術スタック

- Laravel 10
- PHP 8.2+
- Filament 3.2 (管理画面)
- Livewire 3.5
- Cloudinary (画像管理)
- MySQL

## 環境変数

必要な環境変数 (`.env.example`を参照):

```
APP_NAME=
APP_URL=
DB_CONNECTION=
DB_DATABASE=
CLOUDINARY_URL=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

## インストール

1. リポジトリをクローン
2. `composer install` を実行
3. `npm install` を実行
4. `.env.example` を `.env` にコピーし、環境変数を設定
5. `php artisan key:generate` を実行
6. `php artisan migrate` を実行
7. `/admin` から管理画面にアクセス

## デプロイ

このプロジェクトはHerokuへのデプロイを想定しています。Herokuの設定で必要な環境変数をすべて設定してください。

## ライセンス

MIT License

## Copyright

©2024 y2 records inc.
