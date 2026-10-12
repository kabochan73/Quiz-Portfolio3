{{--
    共通コンポーネントの見本ページ(implementation-plan.md 5-6)。開発環境でだけ開ける。
    部品の見た目をまとめて確かめ、調整するためのページ。部品を足したら、ここにも並べる。
--}}
<x-layouts.app title="Components">
    <x-ui.breadcrumb :items="[
        ['label' => 'Dev', 'href' => '#'],
        ['label' => 'Components', 'href' => '#'],
    ]" />

    <x-ui.page-header title="Components">
        <x-slot:actions>
            <x-ui.dropdown>
                <x-ui.dropdown-item href="#">Edit</x-ui.dropdown-item>
                <x-ui.dropdown-item variant="danger" x-on:click="$dispatch('open-modal', 'demo-delete')">Delete</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
        <x-slot:description>Every shared component on one page.</x-slot:description>
    </x-ui.page-header>

    <div class="space-y-10">
        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Colors</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    'bg-ink' => 'ink',
                    'bg-ink-hover' => 'ink-hover',
                    'bg-neutral-900' => 'neutral-900',
                    'bg-neutral-700' => 'neutral-700',
                    'bg-neutral-500' => 'neutral-500',
                    'bg-neutral-300' => 'neutral-300',
                    'bg-neutral-200' => 'neutral-200',
                    'bg-neutral-100' => 'neutral-100',
                ] as $class => $name)
                    <div>
                        <div class="{{ $class }} h-12 rounded-lg border border-neutral-200"></div>
                        <p class="mt-1 text-xs text-neutral-500">{{ $name }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Typography</h2>
            <p class="text-4xl font-semibold text-neutral-900 tabular-nums">72</p>
            <p class="text-2xl font-semibold tracking-tight text-neutral-900">Page heading</p>
            <p class="text-lg font-semibold text-neutral-900">Section heading</p>
            <p class="text-base font-medium text-neutral-900">Card heading</p>
            <p class="text-body whitespace-pre-wrap" lang="ja">本文です。TCPはコネクション型で、データが確実に届いたかを確かめながら送ります。
改行はそのまま表示します。</p>
            <p class="text-sm">UI text</p>
            <p class="text-xs text-neutral-500">Supporting text · Oct 8, 14:32</p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Buttons</h2>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="danger">Delete</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
                <x-ui.button href="#" disabled>Disabled link</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm">Small</x-ui.button>
                <x-ui.button size="sm" variant="secondary">Small</x-ui.button>
                <x-ui.button><x-icon name="plus" class="size-4" />With icon</x-ui.button>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold text-neutral-900">Form fields</h2>
            <x-ui.input name="demo_name" label="Name" value="Networking" />
            <x-ui.input name="demo_error" label="Name (with error)" />
            <x-ui.textarea name="demo_body" label="Question" value="TCPとUDPの違いを説明してください。" maxlength="2000" lang="ja" />
            <x-ui.select name="demo_select" label="Select" :options="['a' => 'Option A', 'b' => 'Option B']" value="b" />
            <x-ui.radio-group
                name="demo_level"
                label="Grading level"
                :options="collect(\App\Enums\GradingLevel::cases())->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()"
                value="normal"
            />
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Badges</h2>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ([100, 80, 79, 60, 59, 0, null] as $score)
                    <x-ui.score-badge :score="$score" />
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="flex items-center gap-1.5"><x-ui.score-badge :score="78" /><x-ui.score-delta :current="78" :previous="62" /></span>
                <span class="flex items-center gap-1.5"><x-ui.score-badge :score="45" /><x-ui.score-delta :current="45" :previous="53" /></span>
                <span class="flex items-center gap-1.5"><x-ui.score-badge :score="70" /><x-ui.score-delta :current="70" :previous="70" /></span>
                <span class="flex items-center gap-1.5"><x-ui.score-badge :score="82" /><x-ui.score-delta :current="82" /></span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (\App\Enums\AttemptStatus::cases() as $status)
                    <x-ui.status-badge :status="$status" />
                @endforeach
                @foreach (\App\Enums\AttemptMode::cases() as $mode)
                    <x-ui.badge>{{ $mode->label() }}</x-ui.badge>
                @endforeach
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Cards</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.card>
                    <p class="text-base font-medium text-neutral-900">Card</p>
                    <p class="mt-1 text-xs text-neutral-500">3 sections · 24 questions</p>
                </x-ui.card>
                <x-ui.card href="#">
                    <p class="text-base font-medium text-neutral-900">Link card</p>
                    <p class="mt-1 text-xs text-neutral-500">Hover to see the border.</p>
                </x-ui.card>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Feedback</h2>
            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" x-on:click="$dispatch('toast', { type: 'success', message: 'Category created.' })">Show success toast</x-ui.button>
                <x-ui.button variant="secondary" x-on:click="$dispatch('toast', { type: 'error', message: 'No weak questions.' })">Show error toast</x-ui.button>
                <x-ui.button variant="secondary" x-on:click="$dispatch('open-modal', 'demo-delete')">Open confirm modal</x-ui.button>
            </div>
            <x-ui.empty-state message="No questions yet." action="Add question" action-href="#" />
            <x-ui.empty-state message="No attempts yet." />
            <x-ui.card class="space-y-3">
                <x-ui.skeleton class="h-4 w-32" />
                <x-ui.skeleton class="h-4 w-full" />
                <x-ui.skeleton class="h-4 w-2/3" />
            </x-ui.card>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-neutral-900">Icons</h2>
            <div class="flex flex-wrap gap-4 text-neutral-700">
                @foreach (['arrow-right-start-on-rectangle', 'bars-3', 'check', 'check-circle', 'chevron-down', 'chevron-right', 'document-text', 'ellipsis-horizontal', 'exclamation-circle', 'home', 'plus', 'x-mark'] as $icon)
                    <x-icon :name="$icon" :label="$icon" />
                @endforeach
            </div>
        </section>
    </div>

    <x-ui.confirm-modal name="demo-delete" title="Delete this category?" action="#">
        This will also delete 3 sections, 24 questions, and 12 attempts.
    </x-ui.confirm-modal>
</x-layouts.app>
