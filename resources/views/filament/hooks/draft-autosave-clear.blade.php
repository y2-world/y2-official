{{-- 登録・保存のあと、移った先の画面で、保存した画面の下書きを消す（HasDraftAutosave::clearDraftAutosave） --}}
@if (session('draft_autosave_clear'))
    <script>
        try { localStorage.removeItem('filament-draft:' + @js(session('draft_autosave_clear'))); } catch (e) {}
    </script>
@endif
