{{--
    Shared Trix rich-text field, used wherever a field needs short,
    editable bullet-point content (Gap Analysis, the merged Competitive
    Analysis field). $model is the Livewire-bound dotted path (e.g.
    "form.gap"); $value is that same field's current value, rendered
    directly; $fieldId must be unique on the page (Trix's custom element
    binds to a specific hidden input by id).

    The hidden input's `value="..."` attribute (not x-model) is what seeds
    Trix's starting content — Trix reads it the instant its custom element
    connects to the DOM, which can happen before Alpine's own init pass has
    written anything via x-model, so relying on x-model alone left the
    editor starting blank even when the field already had real content.
--}}
<div class="field full">
    @if ($label ?? null)
        <label @class(['gap-title' => ($labelClass ?? null) === 'gap-title'])>{{ $label }}</label>
    @endif
    <div
        class="trix-field"
        x-data="{
            value: $wire.entangle('{{ $model }}'),
            init() {
                // Livewire can update this field from outside the editor
                // (e.g. the Competitive Analysis poll reloading $form once
                // an AI generation run finishes) — Trix only renders what
                // was loaded at mount time, so push external changes into
                // the editor explicitly. Guarded against the field's own
                // trix-change updates re-triggering this.
                this.$watch('value', (newValue) => {
                    if (this.$refs.editor.editor && newValue !== this.$refs.input.value) {
                        this.$refs.editor.editor.loadHTML(newValue || '');
                    }
                });
            },
        }"
    >
        <input id="{{ $fieldId }}" type="hidden" x-ref="input" x-model="value" value="{{ $value ?? '' }}">
        <trix-editor x-ref="editor" input="{{ $fieldId }}" x-on:trix-change="value = $refs.input.value"></trix-editor>
    </div>
</div>
