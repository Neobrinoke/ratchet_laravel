<template>
    <div class="message_box">
        <div class="message_list" ref="message_list">
            <template v-for="message in messages">
                <div class="message" :class="{ incoming: message.receiver_id === userId }">
                    <img v-if="message.receiver_id === userId" src="https://ptetutorials.com/images/user-profile.png" alt="sunil">
                    <div class="message_details">
                        <div class="message_content">
                            <p>{{ message.content }}</p>
                            <span class="time_date">{{ (new Date(message.sent_at)).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div class="message_input">
            <input type="text" ref="message_input" title="message" placeholder="Type a message" v-model="message" @keyup.enter="handleSendMessage()">
            <button type="button" @click.prevent="handleSendMessage()"><i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
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
            receiverId: {
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
            axios.get('messages?receiver_id=' + this.receiverId).then((res) => {
                console.log('mounted');
                this.messages = res.data;
            }).catch((err) => {
                console.error(err);
            }).then(() => {
                this.scrollToBottom();

                this.$emit('on-mounted', this);
            });
        },
        methods: {
            handleReceiveMessage(message) {
                console.log('handleReceiveMessage');
                this.messages.push(message);

                this.scrollToBottom();
            },
            handleSendMessage() {
                if (!this.message) {
                    return;
                }

                this.ws.send(JSON.stringify({
                    action: 'message',
                    receiverId: this.receiverId,
                    content: this.message,
                    date: new Date(),
                }));

                this.message = '';
                this.$refs.message_input.focus();
            },
            scrollToBottom() {
                this.$nextTick(() => {
                    this.$refs.message_list.scrollTop = this.$refs.message_list.scrollHeight;
                });
            },
        },
    }
</script>

<style lang="scss" scoped>
    .message_box {
        float: left;
        padding: 30px 15px 0 15px;
        width: 60%;

        .message_list {
            height: 516px;
            overflow-y: auto;

            .message {
                margin: 13px 0 13px;
                overflow: hidden;

                .message_content {
                    float: right;
                    width: 50%;

                    p {
                        background: #05728f none repeat scroll 0 0;
                        border-radius: 3px;
                        font-size: 14px;
                        margin: 0;
                        color: #fff;
                        padding: 5px 10px 5px 12px;
                        width: 100%;
                    }

                    .time_date {
                        color: #747474;
                        display: block;
                        font-size: 12px;
                        margin: 8px 0 0;
                        float: right;
                    }
                }

                &.incoming {
                    img {
                        display: inline-block;
                        width: 6%;
                    }

                    .message_details {
                        display: inline-block;
                        padding: 0 0 0 10px;
                        vertical-align: top;
                        width: 92%;
                    }

                    .message_content {
                        float: none;

                        p {
                            background-color: #ebebeb;
                            color: #646464;
                        }

                        .time_date {
                            float: none;
                        }
                    }
                }
            }
        }

        .message_input {
            border-top: 1px solid #c4c4c4;
            position: relative;

            input {
                background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
                border: medium none;
                color: #4c4c4c;
                font-size: 15px;
                min-height: 48px;
                width: 100%;
                outline: none;
            }

            button {
                background: #05728f none repeat scroll 0 0;
                border: medium none;
                border-radius: 50%;
                color: #fff;
                cursor: pointer;
                font-size: 17px;
                height: 33px;
                position: absolute;
                right: 0;
                top: 8px;
                width: 33px;
                outline: none;
            }
        }
    }
</style>
