<template>
    <div class="mesgs">
        <div class="msg_history" ref="message_list">
            <template v-for="message in messages">
                <div class="outgoing_msg" v-if="message.is_mine">
                    <div class="sent_msg">
                        <p>{{ message.content }}</p>
                        <span class="time_date text-right">{{ (new Date(message.date)).toLocaleString() }}</span>
                    </div>
                </div>
                <div class="incoming_msg" v-else>
                    <div class="incoming_msg_img"><img src="https://ptetutorials.com/images/user-profile.png" alt="sunil"></div>
                    <div class="received_msg">
                        <div class="received_withd_msg">
                            <p>{{ message.content }}</p>
                            <span class="time_date text-left">{{ (new Date(message.date)).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="type_msg">
            <div class="input_msg_write">
                <input type="text" ref="message_input" title="message" v-model="message" class="write_msg" placeholder="Type a message">
                <button class="msg_send_btn" type="button" @click.prevent="handleSendMessage()"><i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'Message',
        props: {
            userId: {
                type: Number,
                required: true,
            },
            toId: {
                type: Number,
                required: true,
            },
            ws: {
                type: WebSocket,
                required: true,
            },
        },
        data() {
            return {
                message: '',
                messages: [],
            };
        },
        mounted() {
            this.$emit('on-mounted', this);

            this.scrollToBottom();
        },
        methods: {
            handleReceiveMessage(message) {
                this.messages.push(message);

                this.scrollToBottom();
            },
            handleSendMessage() {
                const message = {
                    action: 'message',
                    to: this.toId,
                    content: this.message,
                    date: new Date(),
                    is_mine: true,
                };

                this.ws.send(JSON.stringify(message));

                this.messages.push(message);

                this.message = '';
                this.$refs.message_input.focus();

                this.scrollToBottom();
            },
            scrollToBottom() {
                setTimeout(() => {
                    this.$refs.message_list.scrollTop = this.$refs.message_list.scrollHeight;
                }, 100);
            }
        },
    }
</script>
