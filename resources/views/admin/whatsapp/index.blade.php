<x-app-layout title="WhatsApp">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Conexao WhatsApp</h2>
                <p class="text-sm text-slate-500">Gerencie a instancia que envia mensagens para os grupos das ligas.</p>
            </div>
            <a href="{{ route('admin.ligas.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
                Voltar para ligas
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Status da conexao</h3>

                @if($connection)
                    @php
                        $statusRaw = $connection->status ?? 'desconhecido';
                        $statusValue = is_string($statusRaw) ? strtolower(trim($statusRaw)) : '';
                        $statusLabel = in_array($statusValue, ['open', 'connected', 'online'], true)
                            ? 'Conectado'
                            : $statusRaw;
                    @endphp
                    <div class="mt-4 space-y-3 text-sm text-slate-700">
                        <div>
                            <span class="text-xs uppercase tracking-wide text-slate-400">Instancia</span>
                            <p class="font-semibold text-slate-900">{{ $connection->instance_name }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-slate-400">Status</span>
                            <p class="font-semibold text-slate-900">{{ $statusLabel }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-slate-400">Ultima atualizacao</span>
                            <p class="font-semibold text-slate-900">{{ $connection->last_status_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <form action="{{ route('admin.whatsapp.instance.create') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50">
                                Gerar nova instancia
                            </button>
                        </form>
                        <form action="{{ route('admin.whatsapp.status', $connection) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                Atualizar status
                            </button>
                        </form>
                        <form action="{{ route('admin.whatsapp.connect', $connection) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-500">
                                Gerar QR Code
                            </button>
                        </form>
                        <form action="{{ route('admin.whatsapp.restart', $connection) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                Reiniciar
                            </button>
                        </form>
                        <form action="{{ route('admin.whatsapp.logout', $connection) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                Desconectar
                            </button>
                        </form>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-600">Nenhuma instancia cadastrada ainda.</p>
                    <form action="{{ route('admin.whatsapp.instance.create') }}" method="POST" class="mt-6">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                            Criar nova instancia
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">QR Code</h3>

                @if($connection && $connection->qr_code)
                    @php
                        $qrCode = $connection->qr_code;
                        $qrSrc = str_starts_with($qrCode, 'data:image')
                            ? $qrCode
                            : 'data:image/png;base64,' . $qrCode;
                    @endphp
                    <div class="mt-4 flex flex-col items-center gap-4">
                        <img
                            src="{{ $qrSrc }}"
                            alt="QR Code da conexao"
                            class="h-56 w-56 rounded-xl border border-slate-200 bg-white object-contain"
                        >
                        <p class="text-xs text-slate-500">Abra o WhatsApp do administrador e escaneie o QR Code.</p>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-600">Gere um QR Code para conectar o WhatsApp.</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
            <p class="font-semibold text-slate-700">Proximo passo</p>
            <p class="mt-1">Depois de conectar, selecione o grupo em cada liga usando o campo JID do WhatsApp.</p>
        </div>
    </div>
</x-app-layout>
