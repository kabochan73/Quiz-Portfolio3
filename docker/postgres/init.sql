-- DB コンテナを初めて起動したときだけ実行される(postgres イメージの仕様)。
-- 開発用の quiz とは別に、テスト専用の DB を作る(phpunit.xml)。
CREATE DATABASE quiz_testing OWNER quiz;
