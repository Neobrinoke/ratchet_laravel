<template>
    <div class="card">
        <div class="card-header">Dashboard</div>
        <div class="card-body">
            <h1>Content</h1>
            <button class="btn btn-primary" @click="sendMessage()">Envoyer</button>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            userId: {
                type: Number,
                required: true,
            },
        },
        data() {
            return {
                ws: null,
            };
        },
        mounted() {
            this.ws = new WebSocket('ws://localhost:8090');
            this.ws.onopen = this.onOpen;
            this.ws.onmessage = this.onMessage;
        },
        methods: {
            onOpen(e) {
                console.log('Connection established !');
                this.ws.send(JSON.stringify({action: 'register', userId: this.userId}));
            },
            onMessage(e) {
                let message = JSON.parse(e.data);
                if (message.action) {
                    switch (message.action) {
                        case 'message':
                            console.log('new message from ' + message.from);
                            break;
                    }
                }
            },
            sendMessage() {
                console.log('sendMessage');
                this.ws.send(JSON.stringify({
                    action: 'message',
                    to: 2,
                    content: 'blabla',
                }));
            },
        },
    }
</script>
