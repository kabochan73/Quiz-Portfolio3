import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import answerForm, { removeDraft } from './answer-form';
import attemptStatus from './attempt-status';
import toastStack from './toast';

// collapse: 結果画面の問題カードなどの開閉(design-guide.md 5章 x-collapse)
// focus: モーダル表示中にフォーカスを閉じ込める(design-guide.md 8章 x-trap)
Alpine.plugin(collapse);
Alpine.plugin(focus);

Alpine.data('answerForm', answerForm);
Alpine.data('attemptStatus', attemptStatus);
// 結果画面を開いたら、その回答フォームの下書きを消す(architecture.md 7章)
Alpine.data('draftCleaner', (key) => ({ init: () => removeDraft(key) }));
Alpine.data('toastStack', toastStack);

window.Alpine = Alpine;
Alpine.start();
