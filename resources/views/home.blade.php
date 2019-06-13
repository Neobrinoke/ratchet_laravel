@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">Dashboard</div>
        <div class="card-body">
            <h1>Content</h1>
            <button class="btn btn-primary" onclick="sendMessage()">Envoyer</button>
        </div>
    </div>
@endsection

@section('js')
    <script defer>
        let conn = new WebSocket('ws://localhost:8090');
        conn.onopen = function (e) {
            console.log('Connection established !');
            conn.send(JSON.stringify({action: 'register', userId: parseInt('{{ auth()->id() }}')}));
        };

        conn.onmessage = function (e) {
            let message = JSON.parse(e.data);
            if (message.action) {
                switch (message.action) {
                    case 'message':
                        console.log('new message from ' + message.from);
                        break;
                }
            }
        };

        function sendMessage() {
            conn.send(JSON.stringify({
                action: 'message',
                to: 2,
                content: 'blabla',
            }));
        }
    </script>
@endsection
