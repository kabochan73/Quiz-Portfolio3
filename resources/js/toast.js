/**
 * トースト(design-guide.md 7.5)。画面の右下(スマホでは下中央)に一時的に出し、4秒で消す。
 *
 * 出し方は2つ。
 * - サーバーから: コントローラで ->with('toast', [...]) を渡すと、次の画面で <x-ui.toast-stack> が最初に出す
 * - 画面の JS から: window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }))
 *   (回答フォームの「Draft restored.」など。screens.md 2.9)
 */
const DISPLAY_MS = 4000;

export default function toastStack(initial = []) {
    return {
        toasts: [],
        nextId: 0,

        init() {
            initial.forEach((toast) => this.add(toast));
        },

        add({ type = 'success', message }) {
            const id = this.nextId++;
            this.toasts.push({ id, type, message });
            setTimeout(() => this.remove(id), DISPLAY_MS);
        },

        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    };
}
