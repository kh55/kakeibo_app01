# PR用: 予算管理の完成

## 背景
家計簿アプリの予算管理を「カテゴリ別予算の設定と進捗確認」まで完成させるため。既存の予算CRUD・年月絞り込みに加え、実績表示と重複防止を追加する必要があった。

## 概要
- 予算一覧に実績額・残り・達成率/超過を表示する
- 登録・編集時に同一年月・同一カテゴリの重複をバリデーションで拒否する
- 上記を Feature テストで検証する

## 実施内容
- **BudgetController**: index で Transaction を集計し各予算に実績・残り・超過フラグを付与。store/update で unique バリデーション（user, year, month, category_id）と DB 重複時のフォールバック
- **budgets/index.blade.php**: 実績額・残り・達成率/超過の列を追加、取引なしは 0 円表示、残り負は「超過」表示
- **BudgetControllerTest**: 一覧の実績付与・年月フィルタ・リダイレクト・当該ユーザーのみ表示・重複登録/更新のエラー・他ユーザー予算の 403 を検証
- **仕様**: `.kiro/specs/budget-management/`（requirements, design, tasks, spec.json, research）

## 参考
- 仕様: `.kiro/specs/budget-management/`
- 要件: 予算の登録・編集・削除、一覧と年月絞り込み、進捗表示、認可とデータ分離、同一年月・カテゴリの重複防止
