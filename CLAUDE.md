# recollect(Quiz-Portfolio3)

自由記述の解答を Claude API が採点する、個人用の学習アプリ(ポートフォリオ v3)。v2(Quiz-Portfolio2)の要件・設計・構成を引き継ぎ、コードをスクラッチで作り直している。v3 での変更点(画面の英語化・白黒のデザイン・ホーム画面・デプロイなど)は requirements.md 9章の決定事項にまとめてある。

## ドキュメント

作業の前に、関係するドキュメントを読むこと。仕様の判断に迷ったら、ドキュメントを根拠にする。

| ファイル | 内容 |
|---|---|
| [doc/requirements.md](doc/requirements.md) | 要件定義(何を作るか)。9章に決定事項の一覧 |
| [doc/design-guide.md](doc/design-guide.md) | UIデザインガイド(色・文字・共通コンポーネント) |
| [doc/screens.md](doc/screens.md) | 画面設計(ワイヤーフレーム・画面遷移・表示する英語の文言) |
| [doc/architecture.md](doc/architecture.md) | DB・採点処理・デプロイの設計 |
| [doc/implementation-plan.md](doc/implementation-plan.md) | 実装の順番(横切り開発)・コミットの粒度・テストの方針 |

仕様を変えた場合は、コードと一緒にドキュメントも更新する。

## 技術スタック

- PHP 8.5 / Laravel 13(Blade、Livewire は使わない)
- Tailwind CSS v4 + Alpine.js
- PostgreSQL、キューは database ドライバ
- AI採点: Claude API
- テスト: Pest
- デプロイ: Railway(web / worker / PostgreSQL。architecture.md 8章)

## 開発環境

PHP / Composer はローカルに不要で、Docker で完結させる。CSS / JS のビルド(Vite)だけは手元の Node.js(22 以上)で動かす。

```sh
docker compose up -d                       # app / nginx / db / worker を起動
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
npm install
npm run dev                                # Vite の開発サーバー(画面の自動再読み込み)
docker compose exec app php artisan test   # テスト(DB は quiz_testing を使う)
docker compose exec app ./vendor/bin/pint  # コード整形
```

- アプリ: http://localhost:8000(`npm run dev` を動かしていないときは、先に `npm run build` が必要)
- `.env` の `GRADING_DRIVER=fake` にすると、Claude API を呼ばずに固定の採点結果を返す(画面の作り込みはこれで行う)
- `.env` を変えたら `docker compose restart app worker` で再起動する。worker は環境変数を起動時のまま使い続けるため

## 開発ルール

### 進め方

- **横切り開発**: implementation-plan.md のフェーズ順に、層ごと(データ → ロジック → HTTP → 画面)に作る。上の層の都合で下の層を先回りして作らない
- 機能の追加はしない。仕様は doc/ に書かれたものだけ

### UI

- 画面の文言はすべて英語。screens.md の「4. 表示する文言」に合わせる(簡単な英語、短く、「!」は使わない)。AI の採点コメントだけは日本語
- 白黒基調。色・サイズは `resources/css/app.css` の `@theme` のトークンを使う。色コードや任意の値(`text-[#xxx]` など)を直接書かない。色を使うのは点数と状態のバッジだけ
- ボタン・入力欄・カード・バッジなどは `resources/views/components/ui/` の共通コンポーネントを使う。画面ごとに同じスタイルを書き直さない
- 新しい部品が必要になったら、まず共通コンポーネントとして作り、design-guide.md に追記する
- スマホ(幅 375px)と PC(幅 1280px)の両方で見た目を確認する
- 削除の確認に `confirm()` を使わない。確認モーダルを使う

### コード

- コメントは日本語で書き、「なぜそうしたか」を残す。仕様に由来する場合は根拠のドキュメントの章を書く(例: `// requirements.md 3.3: 〜のため`)
- 入力チェックは FormRequest で行う。ユーザーは1人なので Policy は作らない。URL の親子関係は `scopeBindings` で確認する(architecture.md 6章)
- 複数のテーブルを更新する処理はトランザクションで囲む
- 外部 API(Claude)を呼ぶ処理は `GradingService` インターフェースの後ろに置き、テストではフェイクに差し替える
- 状態や種別は文字列で比べず、Enum を使う
- 挑戦・回答の新旧は ID で決める(architecture.md 2.7)

### テスト

- 機能を追加・変更したら、同じコミットでテストを追加・更新する
- DB や API に依存しないロジックは Unit テスト、画面や Job の流れは Feature テストで確認する
- テストで実際の Claude API を呼ばない

### Git

- コミットメッセージは日本語で、何をしたかを1行で書く(例: 「カテゴリのCRUD機能を実装」)
- 1コミットは5ファイル前後(テストを含めて3〜8ファイル)。自動生成ファイルが多いものは例外
- コミット前に Pint とテストを通す
- API キー・パスワードはコミットしない(リポジトリは公開する)
