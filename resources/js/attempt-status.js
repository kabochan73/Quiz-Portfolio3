/**
 * 結果画面の採点中の表示(screens.md 2.10 (a)、architecture.md 4.8)。
 * 3秒ごとに採点の状態を確かめ、completed か failed になったらページを読み直して結果を出す。
 * 5分たっても終わらなければ確かめるのをやめ、時間がかかっていることを伝える。
 */
const POLL_INTERVAL_MS = 3000;
const GIVE_UP_AFTER_MS = 5 * 60 * 1000;
const FINISHED = ['completed', 'failed'];

export default function attemptStatus({ statusUrl }) {
    return {
        timedOut: false,
        startedAt: Date.now(),
        timer: null,

        init() {
            this.timer = setInterval(() => this.check(), POLL_INTERVAL_MS);
        },

        destroy() {
            clearInterval(this.timer);
        },

        async check() {
            if (Date.now() - this.startedAt > GIVE_UP_AFTER_MS) {
                clearInterval(this.timer);
                this.timedOut = true;
                return;
            }

            try {
                const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                if (! response.ok) {
                    return;
                }

                const { status } = await response.json();
                if (FINISHED.includes(status)) {
                    clearInterval(this.timer);
                    window.location.reload();
                }
            } catch {
                // 通信が一時的に切れても、次の確認で取り戻せるので何もしない
            }
        },
    };
}
