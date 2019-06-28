<template>
    <div>
        <div class="message_box">
            <div class="message_header">
                <h4>{{ chat.name }}</h4>
                <button class="btn no-shadow btn-secondary btn-sm float-right" type="button" data-toggle="modal" :data-target="'#addMembers' + chat.id">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            <div class="message_list" ref="message_list">
                <semipolar-spinner v-if="loading" style="margin: 0 auto;" :animation-duration="1000" :size="65" color="#05728f"/>

                <template v-for="message in messages">
                    <div class="message" :class="{ incoming: message.sender_id !== currentUser.id }">
                        <img v-if="message.sender_id !== currentUser.id" :src="message.sender.profile_picture_url" alt="sunil">
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
                <input type="text" ref="message_input" title="message" :disabled="loading" placeholder="Type a message" v-model="message" @keyup.enter="handleSendMessage()">
                <button type="button" :disabled="loading" @click.prevent="handleSendMessage()"><i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
            </div>
        </div>

        <div class="modal" :id="'addMembers' + chat.id">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Ajouter des membres</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger" v-show="newPotentialsMembers.length === 0">
                            Aucun nouveau membres à ajouter
                        </div>

                        <div class="form-group" v-show="newPotentialsMembers.length > 0">
                            <label>Sélectionnez des membres à ajouter au chat</label>
                            <select class="form-control" v-model="newMembers" multiple>
                                <option v-for="user in newPotentialsMembers" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" @click.prevent="addMembers()">Ajouter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    const ACTION_REGISTER_CHAT = 'register_chat';
    const ACTION_SEND_CHAT_MESSAGE = 'send_chat_message';
    const ACTION_ADD_CHAT_MEMBERS = 'add_chat_members';

    import {SemipolarSpinner} from 'epic-spinners';

    export default {
        name: 'Chat',
        components: {
            SemipolarSpinner,
        },
        props: {
            chat: {
                type: Object,
                required: true,
            },
            currentUser: {
                type: Object,
                required: true,
            },
            wsInstance: {
                type: WebSocket,
                required: true,
            },
            users: {
                type: Array,
                required: true,
            },
        },
        data() {
            return {
                messagePagination: null,
                messages: [],
                loading: false,
                message: '',
                newMembers: [],
            };
        },
        mounted() {
            this.wsInstance.send(JSON.stringify({
                action: ACTION_REGISTER_CHAT,
                chat_id: this.chat.id,
            }));

            this.$emit('on-mounted', this);

            this.$refs.message_list.addEventListener('scroll', this.handleScrollMessages);
        },
        beforeDestroy() {
            this.$refs.message_list.removeEventListener('scroll', this.handleScrollMessages);
        },
        methods: {
            handleReceiveMessage(message) {
                this.messages.push(message);

                this.scrollToBottom();
            },
            handleReceiveNotification(notification) {
                console.log(notification);
            },
            handleSendMessage() {
                if (!this.message) {
                    return;
                }

                this.wsInstance.send(JSON.stringify({
                    action: ACTION_SEND_CHAT_MESSAGE,
                    chat_id: this.chat.id,
                    content: this.message,
                    date: new Date(),
                }));

                this.message = '';
                this.$refs.message_input.focus();
            },
            loadMessages() {
                this.loading = true;

                axios.get('/chats/' + this.chat.id + '/messages').then((res) => {
                    this.messagePagination = res.data;
                    this.messages = this.messagePagination.data.reverse();
                }).catch((err) => {
                    console.error(err);
                }).then(() => {
                    this.chat.is_load = true;
                    this.loading = false;
                    this.scrollToBottom();
                });
            },
            handleScrollMessages() {
                if (this.chat.is_load && this.$refs.message_list.scrollTop === 0 && this.messagePagination && this.messagePagination.next_page_url) {
                    this.loading = true;

                    let previousScrollHeight = this.$refs.message_list.scrollHeight;

                    axios.get(this.messagePagination.next_page_url).then((res) => {
                        this.messagePagination = res.data;

                        this.messages = [
                            ...this.messagePagination.data.reverse(),
                            ...this.messages,
                        ];

                        this.$nextTick(() => {
                            this.$refs.message_list.scrollTop = this.$refs.message_list.scrollHeight - previousScrollHeight;
                        });
                    }).catch((err) => {
                        console.error(err);
                    }).then(() => {
                        this.loading = false;
                    });
                }
            },
            addMembers() {
                if (this.newMembers.length === 0) {
                    return;
                }

                this.wsInstance.send(JSON.stringify({
                    action: ACTION_ADD_CHAT_MEMBERS,
                    chat_id: this.chat.id,
                    members: this.newMembers,
                }));

                this.newMembers = [];

                $('#addMembers' + this.chat.id).modal('hide');
            },
            scrollToBottom() {
                this.$nextTick(() => {
                    this.$refs.message_list.scrollTop = this.$refs.message_list.scrollHeight;
                });
            },
        },
        computed: {
            newPotentialsMembers: function () {
                return this.users.filter((user) => {
                    return !this.chat.members.map(m => m.id).includes(user.id);
                });
            },
        },
    }
</script>

<style lang="scss" scoped>
    .message_box {
        float: left;
        padding: 0 15px 0 15px;
        width: 60%;

        .message_header {
            padding: 10px 29px 10px 20px;
            border-bottom: 1px solid #c4c4c4;

            h4 {
                color: #05728f;
                font-size: 23px;
                margin: auto;
                display: inline-block;
            }
        }

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
