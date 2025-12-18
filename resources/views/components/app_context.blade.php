@if (isset($appContext))
    <script>
        window.__APP_CONTEXT__ = @json($appContext);
    </script>
@endif
