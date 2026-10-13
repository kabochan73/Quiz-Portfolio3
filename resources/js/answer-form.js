/**
 * 回答フォーム(screens.md 2.9、architecture.md 7章)。
 *
 * - 進捗: 入力済みの問題を数えて「3 / 8 answered」と出し、入力済みのカードに「Answered」を付ける
 * - 下書き: 入力が止まって1秒後に localStorage へ保存し、ページを開いたときに復元する。「Discard draft」で消す
 *   (送信して結果画面に移ったときの削除は、結果画面の側で行う)
 * - 送信: 未入力の問題があれば送らず、最初の未入力欄へ移ってエラーを出す。送信中はボタンを押せなくする
 * - 離脱: 入力途中でページを離れようとしたら、ブラウザの確認を出す
 *
 * localStorage が使えない環境(プライベートブラウズなど)では、下書きを保存せずにそのまま動く。
 */
const SAVE_DELAY_MS = 1000;
const SAVED_MESSAGE_MS = 3000;

function readDraft(key) {
    try {
        const raw = window.localStorage.getItem(key);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function writeDraft(key, draft) {
    try {
        window.localStorage.setItem(key, JSON.stringify(draft));
        return true;
    } catch {
        return false;
    }
}

export function removeDraft(key) {
    try {
        window.localStorage.removeItem(key);
    } catch {
        // 使えない環境では、もともと保存していない
    }
}

export default function answerForm({ storageKey, defaultGradingLevel }) {
    return {
        answers: {},
        gradingLevel: defaultGradingLevel,
        savedVisible: false,
        submitting: false,
        showErrors: false,
        saveTimer: null,
        savedTimer: null,

        init() {
            // サーバーから返ってきた入力(入力エラーで戻ったとき)を、まず画面から読み取る
            this.textareas().forEach((textarea) => {
                this.answers[textarea.dataset.questionId] = textarea.value;
            });
            this.gradingLevel = this.checkedLevel() ?? this.gradingLevel;

            // 入力エラーで戻ってきたときは、その内容を優先し、下書きでは上書きしない
            if (this.answeredCount === 0) {
                this.restoreDraft();
            }

            window.addEventListener('beforeunload', (event) => {
                if (this.answeredCount > 0 && ! this.submitting) {
                    event.preventDefault();
                }
            });
        },

        get answeredCount() {
            return Object.values(this.answers).filter((text) => text.trim() !== '').length;
        },

        isAnswered(questionId) {
            return (this.answers[questionId] ?? '').trim() !== '';
        },

        textareas() {
            return Array.from(this.$root.querySelectorAll('textarea[data-question-id]'));
        },

        checkedLevel() {
            return this.$root.querySelector('input[name="grading_level"]:checked')?.value ?? null;
        },

        onInput(event) {
            const questionId = event.target.dataset?.questionId;
            if (questionId !== undefined) {
                this.answers[questionId] = event.target.value;
            }
            if (event.target.name === 'grading_level') {
                this.gradingLevel = event.target.value;
            }
            this.scheduleSave();
        },

        scheduleSave() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.saveDraft(), SAVE_DELAY_MS);
        },

        saveDraft() {
            const saved = writeDraft(storageKey, {
                grading_level: this.gradingLevel,
                answers: this.answers,
                saved_at: new Date().toISOString(),
            });

            if (saved) {
                this.savedVisible = true;
                clearTimeout(this.savedTimer);
                this.savedTimer = setTimeout(() => (this.savedVisible = false), SAVED_MESSAGE_MS);
            }
        },

        restoreDraft() {
            const draft = readDraft(storageKey);
            if (! draft) {
                return;
            }

            let restored = false;
            // 問題 ID で戻すので、問題が追加・削除されていても、残っている問題の下書きは戻る(architecture.md 7章)
            this.textareas().forEach((textarea) => {
                const text = draft.answers?.[textarea.dataset.questionId];
                if (typeof text === 'string' && text !== '') {
                    this.setTextarea(textarea, text);
                    restored = true;
                }
            });

            const level = this.$root.querySelector(`input[name="grading_level"][value="${draft.grading_level}"]`);
            if (level) {
                level.checked = true;
                this.gradingLevel = level.value;
            }

            if (restored) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Draft restored.' } }));
            }
        },

        discardDraft() {
            removeDraft(storageKey);
            clearTimeout(this.saveTimer);
            this.textareas().forEach((textarea) => this.setTextarea(textarea, ''));
            const level = this.$root.querySelector(`input[name="grading_level"][value="${defaultGradingLevel}"]`);
            if (level) {
                level.checked = true;
                this.gradingLevel = level.value;
            }
            this.showErrors = false;
            this.savedVisible = false;
        },

        // 値を入れ、文字数の表示なども更新されるよう input イベントを送る
        setTextarea(textarea, text) {
            textarea.value = text;
            this.answers[textarea.dataset.questionId] = text;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            clearTimeout(this.saveTimer);
        },

        submit(event) {
            const firstEmpty = this.textareas().find((textarea) => textarea.value.trim() === '');

            if (firstEmpty) {
                event.preventDefault();
                this.showErrors = true;
                firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstEmpty.focus({ preventScroll: true });
                return;
            }

            // 送信中はボタンを押せなくする。離脱の確認も出さない
            this.submitting = true;
        },
    };
}
